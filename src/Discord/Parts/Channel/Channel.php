<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Traversable;
use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Channel\MessageRepository;
use Discord\Repository\Channel\OverwriteRepository;
use Discord\Repository\Guild\MemberRepository;
use GuzzleHttp\Psr7\Request;
use React\Promise\Deferred;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 */
class Channel extends Part
{
    const TYPE_TEXT  = 'text';

    const TYPE_VOICE = 'voice';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'topic',
        'guild_id',
        'position',
        'is_private',
        'last_message_id',
        'permission_overwrites',
        'bitrate',
        'recipient',
    ];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'members'    => MemberRepository::class,
        'messages'   => MessageRepository::class,
        'overwrites' => OverwriteRepository::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function afterConstruct()
    {
        if (!array_key_exists('bitrate', $this->attributes) &&
            $this->type != self::TYPE_TEXT
        ) {
            $this->bitrate = 64000;
        }
    }

    /**
     * Gets the recipient attribute.
     *
     * @return User The Recipient.
     */
    public function getRecipientAttribute()
    {
        // Only for PM channels.
        if (! isset($this->attributes['recipient'])) {
            return null;
        }

        return $this->factory->create(User::class, $this->attributes['recipient'], true);
    }

    /**
     * Sets a permission value to the channel.
     *
     * @param Member|Role       $part        Either a Member or Role, permissions will be set on it.
     * @param ChannelPermission $permissions The permissions that define what the Member/Role can and cannot do.
     *
     * @return \React\Promise\Promise
     */
    public function setPermissions(Part $part, ChannelPermission $permissions = null)
    {
        $deferred = new Deferred();

        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return false;
        }

        if (is_null($permissions)) {
            $permissions = $this->factory->create(ChannelPermission::class);
        }

        list($allow, $deny) = $permissions->bitwise;

        $payload = [
            'id'    => $part->id,
            'type'  => $type,
            'allow' => $allow,
            'deny'  => $deny,
        ];

        if (! $this->created) {
            $this->attributes['permission_overwrites'][] = $payload;
            $deferred->resolve();
        } else {
            $this->http->put("channels/{$this->id}/permissions/{$part->id}", $payload)->then(
                \React\Partial\bind_right($this->resolve, $deferred),
                \React\Partial\bind_right($this->reject, $deferred)
            );
        }

        return $deferred->promise();
    }

    /**
     * Fetches a message object from the Discord servers.
     *
     * @param string $id The message snowflake.
     *
     * @return \React\Promise\Promise
     */
    public function getMessage($id)
    {
        $deferred = new Deferred();

        $id = (int) $id;
        ++$id;

        $this->http->get("channels/{$this->id}/messages?before={$id}&limit=1")->then(
            function ($response) use ($deferred) {
                if (count($response) < 1) {
                    return $deferred->reject(new \Exception('Could not find the message.'));
                }

                $messageResponse = array_shift($response);
                $message         = $this->factory->create(Message::class, $messageResponse, true);

                $deferred->resolve($message);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Moves a member to another voice channel.
     *
     * @param Member|int The member to move. (either a Member part or the member ID)
     *
     * @return \React\Promise\Promise
     */
    public function moveMember($member)
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_VOICE) {
            $deferred->reject(new \Exception('You cannot move a member in a text channel.'));

            return $deferred->promise();
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $this->http->patch(
            "guilds/{$this->guild_id}/members/{$member}",
            [
                'channel_id' => $this->id,
            ]
        )->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        // At the moment we are unable to check if the member
        // was moved successfully.

        return $deferred->promise();
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild The guild attribute.
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Creates an invite for the channel.
     *
     * @param int  $max_age   The time that the invite will be valid in seconds.
     * @param int  $max_uses  The amount of times the invite can be used.
     * @param bool $temporary Whether the invite is for temporary membership.
     * @param bool $xkcd      Whether to generate an XKCD invite.
     *
     * @return \React\Promise\Promise
     */
    public function createInvite($max_age = 3600, $max_uses = 0, $temporary = false, $xkcd = false)
    {
        $deferred = new Deferred();

        $this->http->post(
            $this->replaceWithVariables('channels/:id/invites'),
            [
                'validate' => null,

                'max_age'   => $max_age,
                'max_uses'  => $max_uses,
                'temporary' => $temporary,
                'xkcdpass'  => $xkcd,
            ]
        )->then(
            function ($response) use ($deferred) {
                $invite = $this->factory->create(Invite::class, $response, true);

                $deferred->resolve($invite);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @param array|Traversable $messages An array of messages to delete.
     *
     * @return \React\Promise\Promise
     */
    public function deleteMessages($messages)
    {
        $deferred = new Deferred();

        if (!is_array($messages) &&
            !($messages instanceof Traversable)
        ) {
            $deferred->reject(new \Exception('$messages must be an array or implement Traversable.'));

            return $deferred->promise();
        }

        $count = count($messages);

        if ($count == 0) {
            $deferred->reject(new \Exception('You cannot delete 0 messages.'));

            return $deferred->promise();
        } elseif ($count == 1) {
            $deferred->reject(new \Exception('You cannot delete 1 message.'));

            return $deferred->promise();
        }

        $messageID = [];

        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $messageID[] = $message->id;
            } else {
                $messageID[] = $message;
            }
        }

        $this->http->post(
            "channels/{$this->id}/messages/bulk_delete",
            [
                'messages' => $messageID,
            ]
        )->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Fetches message history.
     *
     * @param array $options
     *
     * @return \React\Promise\Promise
     */
    public function getMessageHistory(array $options)
    {
        $deferred = new Deferred();

        $resolver = new OptionsResolver();
        $resolver->setDefaults(['limit' => 100, 'cache' => true]);
        $resolver->setDefined(['before', 'after']);
        $resolver->setAllowedTypes('before', [Message::class, 'string']);
        $resolver->setAllowedTypes('after', [Message::class, 'string']);
        $resolver->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after'])) {
            $deferred->reject(new \Exception('Can only specify before, or after, not both.'));

            return $deferred->promise();
        }

        $url = "channels/{$this->id}/messages?limit={$options['limit']}";
        if (isset($options['before'])) {
            $url .= '&before='.($options['before'] instanceof Message ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $url .= '&after='.($options['after'] instanceof Message ? $options['after']->id : $options['after']);
        }

        $this->http->get($url, null, [], $options['cache'] ? null : 0)->then(
            function ($response) use ($deferred) {
                $messages = new Collection();

                foreach ($response as $message) {
                    $message = $this->factory->create(Message::class, $message, true);
                    $messages->push($message);
                }

                $deferred->resolve($messages);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Returns the channels invites.
     *
     * @return \React\Promise\Promise
     */
    public function getInvites()
    {
        $deferred = new Deferred();

        $this->http->get($this->replaceWithVariables('channels/:id/invites'))->then(
            function ($response) use ($deferred) {
                $invites = new Collection();

                foreach ($response as $invite) {
                    $invite = $this->factory->create(Invite::class, $invite, true);
                    $invites->push($invite);
                }

                $deferred->resolve($invites);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Sets the permission overwrites attribute.
     *
     * @return void
     */
    public function setPermissionOverwritesAttribute($overwrites)
    {
        $this->attributes['permission_overwrites'] = $overwrites;

        if (!is_null($overwrites)) {
            foreach ($overwrites as $overwrite) {
                $overwrite               = (array) $overwrite;
                $overwrite['channel_id'] = $this->id;

                $this->overwrites->push($overwrite);
            }
        }
    }

    /**
     * Sends a message to the channel if it is a text channel.
     *
     * @param string $text The text to send in the message.
     * @param bool   $tts  Whether the message should be sent with text to speech enabled.
     *
     * @return \React\Promise\Promise
     */
    public function sendMessage($text, $tts = false)
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_TEXT) {
            $deferred->reject(new \Exception('You cannot send a message to a voice channel.'));

            return $deferred->promise();
        }

        $this->http->post(
            "channels/{$this->id}/messages",
            [
                'content' => $text,
                'tts'     => $tts,
            ]
        )->then(
            function ($response) use ($deferred) {
                $message = $this->factory->create(Message::class, $response, true);
                $this->messages->push($message);

                $deferred->resolve($message);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Sends a file to the channel if it is a text channel.
     *
     * @param string $filepath The path to the file to be sent.
     * @param string $filename The name to send the file as.
     * @param string $content  Message content to send with the file.
     * @param bool   $tts      Whether to send the message with TTS.
     *
     * @return \React\Promise\Promise
     */
    public function sendFile($filepath, $filename, $content = null, $tts = false)
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_TEXT) {
            $deferred->reject(new \Exception('You cannot send a file to a voice channel.'));

            return $deferred->promise();
        }

        if (!file_exists($filepath)) {
            $deferred->reject(new FileNotFoundException("File does not exist at path {$filepath}."));

            return $deferred->promise();
        }

        $multipart = [
            [
                'name'     => 'file',
                'contents' => fopen($filepath, 'r'),
                'filename' => $filename,
            ],
            [
                'name'     => 'tts',
                'contents' => ($tts ? 'true' : 'false'),
            ],
        ];

        $this->http->post(
            "channels/{$this->id}/messages",
            null,
            [],
            null,
            false,
            [
                'multipart' => $multipart,
            ]
        )->then(
            function ($response) use ($deferred) {
                $message = $this->factory->create(Message::class, $response, true);
                $this->messages->push($message);

                $deferred->resolve($message);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return bool Whether the request succeeded or failed.
     */
    public function broadcastTyping()
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_TEXT) {
            $deferred->reject(new \Exception('You cannot broadcast typing to a voice channel.'));

            return $deferred->promise();
        }

        $this->http->post("channels/{$this->id}/typing")->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->resolve();
    }

    /**
     * Returns the channel type.
     *
     * @return string Either 'text' or 'voice'.
     */
    public function getChannelType()
    {
        switch ($this->type) {
            case 'text':
            case 'voice':
                return $this->type;
                break;
            default:
                return 'text';
                break;
        }
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array The attributes that will be sent when this part is created.
     */
    public function getCreatableAttributes()
    {
        return [
            'name'                  => $this->name,
            'type'                  => $this->getChannelType(),
            'bitrate'               => $this->bitrate,
            'permission_overwrites' => $this->permission_overwrites,
        ];
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array The attributes that will be sent when this part is updated.
     */
    public function getUpdatableAttributes()
    {
        return [
            'name'     => $this->name,
            'topic'    => $this->topic,
            'position' => $this->position,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryAttributes()
    {
        return [
            'channel_id' => $this->id,
        ];
    }
}

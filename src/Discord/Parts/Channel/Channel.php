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

use Discord\Cache\Cache;
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
    const TYPE_TEXT = 'text';
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
        'members' => MemberRepository::class,
        'messages' => MessageRepository::class,
        'overwrites' => OverwriteRepository::class,
    ];

    /**
     * Gets the recipient attribute.
     *
     * @return User The Recipient.
     */
    public function getRecipientAttribute()
    {
        return $this->factory->create(User::class, $this->attributes['recipient'], true);
    }

    /**
     * Sets a permission value to the channel.
     *
     * @param Member|Role     $part     Either a Member or Role, permissions will be set on it.
     * @param Permission|null $allow    The permissions that define what the Member/Role can do.
     * @param Permission|null $disallow The permissions that define what the Member/Role can't do.
     *
     * @return \React\Promise\Promise
     */
    public function setPermissions($part, $allow = null, $deny = null)
    {
        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return false;
        }

        if (is_null($allow)) {
            $allow = new ChannelPermission();
        }

        if (is_null($deny)) {
            $deny = new ChannelPermission();
        }

        $payload = [
            'id' => $part->id,
            'type' => $type,
            'allow' => $allow->perms,
            'deny' => $deny->perms,
        ];

        $this->http->put("channels/{$This->id}/permissions/{$part->id}", $payload)->then(
            \React\Partial\bind_right($this->resolve, $deferred),
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
        return $this->cache->get("guild.{$this->guild_id}");
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

                'max_age' => $max_age,
                'max_uses' => $max_uses,
                'temporary' => $temporary,
                'xkcdpass' => $xkcd,
            ]
        )->then(function ($response) use ($deferred) {
            $invite = $this->factory->create(Invite::class, $response, true);
            $this->cache->set("invite.{$invite->code}", $invite);

            $deferred->resolve($invite);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @param array $messages An array of messages to delete.
     *
     * @return \React\Promise\Promise
     */
    public function deleteMessages(array $messages)
    {
        $deferred = new Deferred();
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

        $this->http->post("channels/{$this->id}/messages/bulk_delete", [
            'messages' => $messageID,
        ])->then(
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
        $resolver->setDefaults(['limit' => 100]);
        $resolver->setDefined(['before', 'after']);
        $resolver->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after'])) {
            $deferred->reject(new \Exception('Can only specify before, or after, not both.'));

            return $deferred->promise();
        }

        $url = "channels/{$this->id}/messages?limit={$options['limit']}";
        if (isset($options['before'])) {
            if ($options['before'] instanceof Message) {
                $deferred->reject(new \Exception('before must be an instance of '.Message::class));

                return $deferred->promise();
            }
            $url .= '&before='.$options['before']->id;
        }
        if (isset($options['after'])) {
            if ($options['after'] instanceof Message) {
                $deferred->reject(new \Exception('after must be an instance of '.Message::class));

                return $deferred->promise();
            }
            $url .= '&after='.$options['after']->id;
        }

        $this->http->get($url)->then(function ($response) use ($deferred) {
            $messages = new Collection();

            foreach ($response as $message) {
                $message = $this->factory->create(Message::class, $message, true);
                $this->cache->set("message.{$message->id}", $message);
                $messages->push($message);
            }

            $deferred->resolve($messages);
        }, \React\Partial\bind_right($this->reject, $deferred));

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

        $this->http->get($this->replaceWithVariables('channels/:id/invites'))->then(function ($response) use ($deferred) {
            $invites = new Collection();

            foreach ($response as $invite) {
                $invite = $this->factory->create(Invite::class, $invite, true);
                $this->cache->set("invites.{$invite->code}", $invite);
                $invites->push($invite);
            }

            $deferred->resolve($invites);
        }, \React\Partial\bind_right($this->reject, $deferred));

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

        if (! is_null($overwrites)) {
            foreach ($overwrites as $overwrite) {
                $overwrite = (array) $overwrite;
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
                'tts' => $tts,
            ]
        )->then(function ($response) use ($deferred) {
            $message = $this->factory->create(Message::class, $response, true);
            $this->cache->set("message.{$message->id}", $message);
            $this->messages->push($message);

            $deferred->resolve($message);
        }, \React\Partial\bind_right($this->reject, $deferred));

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

        if (! file_exists($filepath)) {
            $deferred->reject(new FileNotFoundException("File does not exist at path {$filepath}."));

            return $deferred->promise();
        }

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($filepath, 'r'),
                'filename' => $filename,
            ],
            [
                'name' => 'tts',
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
        )->then(function ($response) use ($deferred) {
            $message = $this->factory->create(Message::class, $response, true);
            $this->cache->set("message.{$message->id}", $message);
            $this->messages->push($message);

            $deferred->resolve($message);
        }, \React\Partial\bind_right($this->reject, $deferred));

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
            'name' => $this->name,
            'type' => $this->getChannelType(),
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
            'name' => $this->name,
            'topic' => $this->topic,
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

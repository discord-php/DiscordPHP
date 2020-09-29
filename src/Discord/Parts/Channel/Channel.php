<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\InvalidOverwriteException;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Channel\MessageRepository;
use Discord\Repository\Channel\OverwriteRepository;
use Discord\Repository\Channel\VoiceMemberRepository as MemberRepository;
use Discord\Repository\Channel\WebhookRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;
use function React\Partial\bind as Bind;
use function React\Promise\reject as Reject;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 *
 * @property string                     $id              The unique identifier of the Channel.
 * @property string                     $name            The name of the channel.
 * @property int                        $type            The type of the channel.
 * @property string                     $topic           The topic of the channel.
 * @property Guild                      $guild           The guild that the channel belongs to. Only for text or voice channels.
 * @property string|null                $guild_id        The unique identifier of the guild that the channel belongs to. Only for text or voice channels.
 * @property int                        $position        The position of the channel on the sidebar.
 * @property bool                       $is_private      Whether the channel is a private channel.
 * @property string                     $last_message_id The unique identifier of the last message sent in the channel.
 * @property int                        $bitrate         The bitrate of the channel. Only for voice channels.
 * @property User                       $recipient       The first recipient of the channel. Only for DM or group channels.
 * @property Collection|User[]          $recipients      A collection of all the recipients in the channel. Only for DM or group channels.
 * @property bool                       $nsfw            Whether the channel is NSFW.
 * @property int                        $user_limit      The user limit of the channel.
 * @property int                        $rate_limit_per_user Amount of seconds a user has to wait before sending a new message.
 * @property string                     $icon            Icon hash.
 * @property string                     $owner_id        The ID of the DM creator. Only for DM or group channels.
 * @property string                     $application_id  ID of the group DM creator if it is a bot.
 * @property string                     $parent_id       ID of the parent channel.
 * @property Carbon                     $last_pin_timestamp When the last message was pinned.
 * @property MemberRepository           $members
 * @property MessageRepository          $messages
 * @property OverwriteRepository        $overwrites
 * @property WebhookRepository          $webhooks
 */
class Channel extends Part
{
    const TYPE_TEXT = 0;
    const TYPE_DM = 1;
    const TYPE_VOICE = 2;
    const TYPE_GROUP = 3;
    const TYPE_CATEGORY = 4;
    const TYPE_NEWS = 5;
    const TYPE_GAME_STORE = 6;

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
        'recipients',
        'nsfw',
        'user_limit',
        'rate_limit_per_user',
        'icon',
        'owner_id',
        'application_id',
        'parent_id',
        'last_pin_timestamp',
    ];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'members' => MemberRepository::class,
        'messages' => MessageRepository::class,
        'overwrites' => OverwriteRepository::class,
        'webhooks' => WebhookRepository::class,
    ];

    /**
     * {@inheritdoc}
     */
    protected function afterConstruct(): void
    {
        if (! array_key_exists('bitrate', $this->attributes) && $this->type != self::TYPE_TEXT) {
            $this->bitrate = 64000;
        }
    }

    /**
     * Gets the is_private attribute.
     *
     * @return bool Whether the channel is private.
     */
    protected function getIsPrivateAttribute(): bool
    {
        return array_search($this->type, [self::TYPE_DM, self::TYPE_GROUP]) !== false;
    }

    /**
     * Gets the recipient attribute.
     *
     * @return User The recipient.
     */
    protected function getRecipientAttribute(): ?User
    {
        return $this->recipients->first();
    }

    /**
     * Gets the recipients attribute.
     *
     * @return Collection A collection of recepients.
     * @throws \Exception
     */
    protected function getRecipientsAttribute(): Collection
    {
        $recipients = new Collection();

        if (array_key_exists('recipients', $this->attributes)) {
            foreach ((array) $this->attributes['recipients'] as $recipient) {
                $recipients->push($this->factory->create(User::class, (array) $recipient, true));
            }
        }

        return $recipients;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild The guild attribute.
     */
    protected function getGuildAttribute(): Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the last pinned message timestamp.
     *
     * @return Carbon
     */
    protected function getLastPinTimestampAttribute(): ?Carbon
    {
        if (isset($this->attributes['last_pin_timestamp'])) {
            return Carbon::parse($this->attributes['last_pin_timestamp']);
        }

        return null;
    }

    /**
     * Returns the channels pinned messages.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    protected function getPinnedMessages(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->http->get($this->replaceWithVariables('channels/:id/pins'))->then(
            function ($response) use ($deferred) {
                $messages = new Collection();

                foreach ($response as $message) {
                    $message = $this->factory->create(Message::class, (array) $message, true);
                    $messages->push($message);
                }

                $deferred->resolve($messages);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Sets permissions in a channel.
     *
     * @param Part  $part  A role or member.
     * @param array $allow An array of permissions to allow.
     * @param array $deny  An array of permissions to deny.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function setPermissions(Part $part, array $allow = [], array $deny = []): PromiseInterface
    {
        $deferred = new Deferred();

        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return Reject(new InvalidOverwriteException('Given part was not one of member or role.'));
        }

        $allow = array_fill_keys($allow, true);
        $deny = array_fill_keys($deny, true);

        $allowPart = $this->factory->create(ChannelPermission::class, $allow);
        $denyPart = $this->factory->create(ChannelPermission::class, $deny);

        $overwrite = $this->factory->create(Overwrite::class, [
            'id' => $part->id,
            'channel_id' => $this->id,
            'type' => $type,
            'allow' => $allowPart->bitwise,
            'deny' => $denyPart->bitwise,
        ]);

        $this->setOverwrite($part, $overwrite)->then(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Sets an overwrite to the channel.
     *
     * @param Part      $part      A role or member.
     * @param Overwrite $overwrite An overwrite object.
     *
     * @return PromiseInterface
     */
    public function setOverwrite(Part $part, Overwrite $overwrite): PromiseInterface
    {
        $deferred = new Deferred();

        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return Reject(new InvalidOverwriteException('Given part was not one of member or role.'));
        }

        $payload = [
            'id' => $part->id,
            'type' => $type,
            'allow' => (string) $overwrite->allow->bitwise,
            'deny' => (string) $overwrite->deny->bitwise,
        ];

        if (! $this->created) {
            $this->attributes['permission_overwrites'][] = $payload;
            $deferred->resolve();
        } else {
            $this->http->put("channels/{$this->id}/permissions/{$part->id}", $payload)->then(
                Bind([$deferred, 'resolve']),
                Bind([$deferred, 'reject'])
            );
        }

        return $deferred->promise();
    }

    /**
     * Fetches a message object from the Discord servers.
     *
     * @param string $id The message snowflake.
     *
     * @return PromiseInterface
     */
    public function getMessage(string $id): PromiseInterface
    {
        return $this->messages->fetch($id);
    }

    /**
     * Moves a member to another voice channel.
     *
     * @param Member|int The member to move. (either a Member part or the member ID)
     *
     * @return PromiseInterface
     */
    public function moveMember($member): PromiseInterface
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_VOICE) {
            $deferred->reject(new \Exception('You cannot move a member in a text channel.'));

            return $deferred->promise();
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $this->http->patch("guilds/{$this->guild_id}/members/{$member}", ['channel_id' => $this->id])->then(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        // At the moment we are unable to check if the member
        // was moved successfully.

        return $deferred->promise();
    }

    /**
     * Creates an invite for the channel.
     *
     * @param array $options An array of options. All fields are optional.
     * @param int   $options ['max_age']   The time that the invite will be valid in seconds.
     * @param int   $options ['max_uses']  The amount of times the invite can be used.
     * @param bool  $options ['temporary'] Whether the invite is for temporary membership.
     * @param bool  $options ['unique']    Whether the invite code should be unique (useful for creating many unique one time use invites).
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function createInvite($options = []): PromiseInterface
    {
        $deferred = new Deferred();

        $this->http->post($this->replaceWithVariables('channels/:id/invites'), $options)->then(
            function ($response) use ($deferred) {
                $invite = $this->factory->create(Invite::class, (array) $response, true);

                $deferred->resolve($invite);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @param array|Traversable $messages An array of messages to delete.
     *
     * @return PromiseInterface
     */
    public function deleteMessages($messages): PromiseInterface
    {
        $deferred = new Deferred();

        if (! is_array($messages) &&
            ! ($messages instanceof Traversable)
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
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Fetches message history.
     *
     * @param array $options
     *
     * @return PromiseInterface
     */
    public function getMessageHistory(array $options): PromiseInterface
    {
        $deferred = new Deferred();

        $resolver = new OptionsResolver();
        $resolver->setDefaults(['limit' => 100, 'cache' => true]);
        $resolver->setDefined(['before', 'after', 'around']);
        $resolver->setAllowedTypes('before', [Message::class, 'string']);
        $resolver->setAllowedTypes('after', [Message::class, 'string']);
        $resolver->setAllowedTypes('around', [Message::class, 'string']);
        $resolver->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after']) ||
            isset($options['before'], $options['around']) ||
            isset($options['around'], $options['after'])) {
            $deferred->reject(new \Exception('Can only specify one of before, after and around.'));

            return $deferred->promise();
        }

        $url = "channels/{$this->id}/messages?limit={$options['limit']}";
        if (isset($options['before'])) {
            $url .= '&before='.($options['before'] instanceof Message ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $url .= '&after='.($options['after'] instanceof Message ? $options['after']->id : $options['after']);
        }
        if (isset($options['around'])) {
            $url .= '&around='.($options['around'] instanceof Message ? $options['around']->id : $options['around']);
        }

        $this->http->get($url, null, [], $options['cache'] ? null : 0)->then(
            function ($response) use ($deferred) {
                $messages = new Collection();

                foreach ($response as $message) {
                    $message = $this->factory->create(Message::class, (array) $message, true);
                    $messages->push($message);
                }

                $deferred->resolve($messages);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Adds a message to the channels pinboard.
     *
     * @param Message $message The message to pin.
     *
     * @return PromiseInterface
     */
    public function pinMessage(Message $message): PromiseInterface
    {
        $deferred = new Deferred();

        if ($message->pinned) {
            return Reject(new \Exception('This message is already pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return Reject(new \Exception('You cannot pin a message to a different channel.'));
        }

        $this->http->put("channels/{$this->id}/pins/{$message->id}")->then(
            function () use (&$message, $deferred) {
                $message->pinned = true;
                $deferred->resolve($message);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Removes a message from the channels pinboard.
     *
     * @param Message $message The message to un-pin.
     *
     * @return PromiseInterface
     */
    public function unpinMessage(Message $message): PromiseInterface
    {
        $deferred = new Deferred();

        if (! $message->pinned) {
            return Reject(new \Exception('This message is not pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return Reject(new \Exception('You cannot un-pin a message from a different channel.'));
        }

        $this->http->delete("channels/{$this->id}/pins/{$message->id}")->then(
            function () use (&$message, $deferred) {
                $message->pinned = false;
                $deferred->resolve($message);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Returns the channels invites.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function getInvites(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->http->get($this->replaceWithVariables('channels/:id/invites'))->then(
            function ($response) use ($deferred) {
                $invites = new Collection();

                foreach ($response as $invite) {
                    $invite = $this->factory->create(Invite::class, (array) $invite, true);
                    $invites->push($invite);
                }

                $deferred->resolve($invites);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Sets the permission overwrites attribute.
     *
     * @param array $overwrites
     */
    protected function setPermissionOverwritesAttribute(array $overwrites): void
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
     * @param string           $text  The text to send in the message.
     * @param bool             $tts   Whether the message should be sent with text to speech enabled.
     * @param Embed|array|null $embed An embed to send.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function sendMessage(string $text, bool $tts = false, $embed = null): PromiseInterface
    {
        if ($embed instanceof Embed) {
            $embed = $embed->getRawAttributes();
        }
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
                'embed' => $embed,
            ]
        )->then(
            function ($response) use ($deferred) {
                $message = $this->factory->create(Message::class, (array) $response, true);
                $this->messages->push($message);

                $deferred->resolve($message);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Sends an embed to the channel if it is a text channel.
     *
     * @param Embed $embed
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function sendEmbed(Embed $embed): PromiseInterface
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_TEXT) {
            $deferred->reject(new \Exception('You cannot send an embed to a voice channel.'));

            return $deferred->promise();
        }

        $this->http->post("channels/{$this->id}/messages", ['embed' => $embed->getRawAttributes()])->then(function ($response) use ($deferred) {
            $message = $this->factory->create(Message::class, (array) $response, true);
            $this->messages->push($message);

            $deferred->resolve($message);
        }, Bind([$deferred, 'reject']));

        return $deferred->promise();
    }

    /**
     * Sends a file to the channel if it is a text channel.
     *
     * @param string      $filepath The path to the file to be sent.
     * @param string|null $filename The name to send the file as.
     * @param string|null $content  Message content to send with the file.
     * @param bool        $tts      Whether to send the message with TTS.
     *
     * @return PromiseInterface
     */
    public function sendFile(string $filepath, ?string $filename = null, ?string $content = null, $tts = false): PromiseInterface
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

        if (is_null($filename)) {
            $filename = basename($filepath);
        }

        $this->http->sendFile($this, $filepath, $filename, $content, $tts)->then(
            function ($response) use ($deferred) {
                $message = $this->factory->create(Message::class, (array) $response, true);
                $this->messages->push($message);

                $deferred->resolve($message);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return PromiseInterface
     */
    public function broadcastTyping(): PromiseInterface
    {
        $deferred = new Deferred();

        if ($this->getChannelType() != self::TYPE_TEXT) {
            $deferred->reject(new \Exception('You cannot broadcast typing to a voice channel.'));

            return $deferred->promise();
        }

        $this->http->post("channels/{$this->id}/typing")->then(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Creates a message collector for the channel.
     *
     * @param callable $filter           The filter function. Returns true or false.
     * @param array    $options
     * @param int      $options['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options['limit'] The amount of messages allowed or false.
     *
     * @return PromiseInterface
     */
    public function createMessageCollector(callable $filter, array $options = []): PromiseInterface
    {
        $deferred = new Deferred();
        $messages = new Collection();
        $timer = null;

        $options = array_merge([
            'time' => false,
            'limit' => false,
        ], $options);

        $eventHandler = function (Message $message) use (&$eventHandler, $filter, $options, &$messages, &$deferred, &$timer) {
            if ($message->channel_id != $this->id) {
                return;
            }
            // Reject messages not in this channel
            $filterResult = call_user_func_array($filter, [$message]);

            if ($filterResult) {
                $messages->push($message);

                if ($options['limit'] !== false && sizeof($messages) >= $options['limit']) {
                    $this->discord->removeListener(Event::MESSAGE_CREATE, $eventHandler);
                    $deferred->resolve($messages);

                    if (! is_null($timer)) {
                        $this->discord->getLoop()->cancelTimer($timer);
                    }
                }
            }
        };
        $this->discord->on(Event::MESSAGE_CREATE, $eventHandler);

        if ($options['time'] !== false) {
            $timer = $this->discord->getLoop()->addTimer($options['time'] / 1000, function () use (&$eventHandler, &$messages, &$deferred) {
                $this->discord->removeListener(Event::MESSAGE_CREATE, $eventHandler);
                $deferred->resolve($messages);
            });
        }

        return $deferred->promise();
    }

    /**
     * Returns the channel type.
     *
     * @return string Either 'text' or 'voice'.
     */
    public function getChannelType(): string
    {
        switch ($this->type) {
            case self::TYPE_TEXT:
            case self::TYPE_VOICE:
                return $this->type;
                break;
            default:
                return self::TYPE_TEXT;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->getChannelType(),
            'bitrate' => $this->bitrate,
            'permission_overwrites' => $this->permission_overwrites,
            'topic' => $this->topic,
            'user_limit' => $this->user_limit,
            'rate_limit_per_user' => $this->rate_limit_per_user,
            'position' => $this->position,
            'parent_id' => $this->parent_id,
            'nsfw' => $this->nsfw,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'topic' => $this->topic,
            'position' => $this->position,
            'parent_id' => $this->parent_id,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'channel_id' => $this->id,
        ];
    }
}

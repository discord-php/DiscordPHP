<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
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
use Discord\Helpers\Deferred;
use Discord\Helpers\Multipart;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 *
 * @property string              $id                  The unique identifier of the Channel.
 * @property string              $name                The name of the channel.
 * @property int                 $type                The type of the channel.
 * @property string              $topic               The topic of the channel.
 * @property Guild               $guild               The guild that the channel belongs to. Only for text or voice channels.
 * @property string|null         $guild_id            The unique identifier of the guild that the channel belongs to. Only for text or voice channels.
 * @property int                 $position            The position of the channel on the sidebar.
 * @property bool                $is_private          Whether the channel is a private channel.
 * @property string              $last_message_id     The unique identifier of the last message sent in the channel.
 * @property int                 $bitrate             The bitrate of the channel. Only for voice channels.
 * @property User                $recipient           The first recipient of the channel. Only for DM or group channels.
 * @property string              $recipient_id        The ID of the recipient of the channel, if it is a DM channel.
 * @property Collection|User[]   $recipients          A collection of all the recipients in the channel. Only for DM or group channels.
 * @property bool                $nsfw                Whether the channel is NSFW.
 * @property int                 $user_limit          The user limit of the channel.
 * @property int                 $rate_limit_per_user Amount of seconds a user has to wait before sending a new message.
 * @property string              $icon                Icon hash.
 * @property string              $owner_id            The ID of the DM creator. Only for DM or group channels.
 * @property string              $application_id      ID of the group DM creator if it is a bot.
 * @property string              $parent_id           ID of the parent channel.
 * @property Carbon              $last_pin_timestamp  When the last message was pinned.
 * @property string|null         $rtc_region          voice region id for the voice channel, automatic when set to null
 * @property int|null            $video_quality_mode  the camera video quality mode of the voice channel, 1 when not present
 * @property MemberRepository    $members             voice channel only - members in the channel
 * @property MessageRepository   $messages            text channel only - messages sent in the channel
 * @property OverwriteRepository $overwrites          permission overwrites
 * @property WebhookRepository   $webhooks            webhooks in the channel
 */
class Channel extends Part
{
    public const TYPE_TEXT = 0;
    public const TYPE_DM = 1;
    public const TYPE_VOICE = 2;
    public const TYPE_GROUP = 3;
    public const TYPE_CATEGORY = 4;
    public const TYPE_NEWS = 5;
    public const TYPE_GAME_STORE = 6;
    public const TYPE_STAGE_CHANNEL = 13;

    /**
     * @inheritdoc
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
        'rtc_region',
        'video_quality_mode',
    ];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'members' => MemberRepository::class,
        'messages' => MessageRepository::class,
        'overwrites' => OverwriteRepository::class,
        'webhooks' => WebhookRepository::class,
    ];

    /**
     * @inheritdoc
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
     * Gets the recipient ID attribute.
     *
     * @return string
     */
    protected function getRecipientIdAttribute(): ?string
    {
        if ($this->recipient) {
            return $this->recipient->id;
        }
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
                if (! $user = $this->discord->users->get('id', $recipient->id)) {
                    $user = $this->factory->create(User::class, $recipient, true);
                }
                $recipients->push($user);
            }
        }

        return $recipients;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
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
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function getPinnedMessages(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_PINS, $this->id))
        ->then(function ($responses) {
            $messages = new Collection();

            foreach ($responses as $response) {
                if (! $message = $this->messages->get('id', $response->id)) {
                    $message = $this->factory->create(Message::class, $response, true);
                }

                $messages->push($message);
            }

            return $messages;
        });
    }

    /**
     * Sets permissions in a channel.
     *
     * @param Part  $part  A role or member.
     * @param array $allow An array of permissions to allow.
     * @param array $deny  An array of permissions to deny.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function setPermissions(Part $part, array $allow = [], array $deny = []): ExtendedPromiseInterface
    {
        if ($part instanceof Member) {
            $type = Overwrite::TYPE_MEMBER;
        } elseif ($part instanceof Role) {
            $type = Overwrite::TYPE_ROLE;
        } else {
            return \React\Promise\reject(new InvalidOverwriteException('Given part was not one of member or role.'));
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

        return $this->setOverwrite($part, $overwrite);
    }

    /**
     * Sets an overwrite to the channel.
     *
     * @param Part      $part      A role or member.
     * @param Overwrite $overwrite An overwrite object.
     *
     * @return ExtendedPromiseInterface
     */
    public function setOverwrite(Part $part, Overwrite $overwrite): ExtendedPromiseInterface
    {
        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->manage_roles) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to edit roles in the specified channel.'));
            }
        }

        if ($part instanceof Member) {
            $type = Overwrite::TYPE_MEMBER;
        } elseif ($part instanceof Role) {
            $type = Overwrite::TYPE_ROLE;
        } else {
            return \React\Promise\reject(new InvalidOverwriteException('Given part was not one of member or role.'));
        }

        $payload = [
            'id' => $part->id,
            'type' => $type,
            'allow' => (string) $overwrite->allow->bitwise,
            'deny' => (string) $overwrite->deny->bitwise,
        ];

        if (! $this->created) {
            $this->attributes['permission_overwrites'][] = $payload;

            return \React\Promise\resolve();
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_PERMISSIONS, $this->id, $part->id), $payload);
    }

    /**
     * Fetches a message object from the Discord servers.
     *
     * @param string $id The message snowflake.
     *
     * @return ExtendedPromiseInterface
     */
    public function getMessage(string $id): ExtendedPromiseInterface
    {
        return $this->messages->fetch($id);
    }

    /**
     * Moves a member to another voice channel.
     *
     * @param Member|int The member to move. (either a Member part or the member ID)
     *
     * @return ExtendedPromiseInterface
     */
    public function moveMember($member): ExtendedPromiseInterface
    {
        if (! $this->allowVoice()) {
            return \React\Promise\reject(new \Exception('You cannot move a member in a text channel.'));
        }

        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->move_members) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to move members in the specified channel.'));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['channel_id' => $this->id]);
    }

    /**
     * Mutes a member on a voice channel.
     *
     * @param Member|int The member to mute. (either a Member part or the member ID)
     *
     * @return \React\Promise\Promise
     */
    public function muteMember($member): ExtendedPromiseInterface
    {
        if (! $this->allowVoice()) {
            return \React\Promise\reject(new \Exception('You cannot mute a member in a text channel.'));
        }

        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->mute_members) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to mute members in the specified channel.'));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['mute' => true]);
    }

    /**
     * Unmutes a member on a voice channel.
     *
     * @param Member|int The member to unmute. (either a Member part or the member ID)
     *
     * @return \React\Promise\Promise
     */
    public function unmuteMember($member): ExtendedPromiseInterface
    {
        if (! $this->allowVoice()) {
            return \React\Promise\reject(new \Exception('You cannot unmute a member in a text channel.'));
        }

        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->mute_members) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to unmute members in the specified channel.'));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['mute' => false]);
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
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function createInvite($options = []): ExtendedPromiseInterface
    {
        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->create_instant_invite) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to create an invite for the specified channel.'));
            }
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'max_age',
                'max_uses',
                'temporary',
                'unique',
            ])
            ->setAllowedTypes('max_age', 'int')
            ->setAllowedTypes('max_uses', 'int')
            ->setAllowedTypes('temporary', 'bool')
            ->setAllowedTypes('unique', 'bool')
            ->setAllowedValues('max_age', range(0, 604800))
            ->setAllowedValues('max_uses', range(0, 100));

        $options = $resolver->resolve($options);

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_INVITES, $this->id), $options)
        ->then(function ($response) {
            return $this->factory->create(Invite::class, $response, true);
        });
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @param array|Traversable $messages An array of messages to delete.
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteMessages($messages): ExtendedPromiseInterface
    {
        if (! is_array($messages) && ! ($messages instanceof Traversable)) {
            return \React\Promise\reject(new \Exception('$messages must be an array or implement Traversable.'));
        }

        $count = count($messages);

        if ($count == 0) {
            return \React\Promise\resolve();
        } elseif ($count == 1 || $this->is_private) {
            foreach ($messages as $message) {
                if ($message instanceof Message ||
                    $message = $this->messages->get('id', $message)
                ) {
                    return $message->delete();
                }

                return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->id, $message));
            }
        } else {
            $messageID = [];

            foreach ($messages as $message) {
                if ($message instanceof Message) {
                    $messageID[] = $message->id;
                } else {
                    $messageID[] = $message;
                }
            }

            $promises = [];

            while (! empty($messageID)) {
                $promises[] = $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_BULK_DELETE, $this->id), ['messages' => array_slice($messageID, 0, 100)]);
                $messageID = array_slice($messageID, 100);
            }

            return \React\Promise\all($promises);
        }
    }

    /**
     * Deletes a given number of messages, in order of time sent.
     *
     * @param int $value
     *
     * @return ExtendedPromiseInterface
     */
    public function limitDelete(int $value): ExtendedPromiseInterface
    {
        return $this->getMessageHistory(['limit' => $value])->then(function ($messages) {
            return $this->deleteMessages($messages);
        });
    }

    /**
     * Fetches message history.
     *
     * @param array $options
     *
     * @return ExtendedPromiseInterface
     */
    public function getMessageHistory(array $options): ExtendedPromiseInterface
    {
        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->read_message_history) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to read the specified channel\'s message history.'));
            }
        }

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
            return \React\Promise\reject(new \Exception('Can only specify one of before, after and around.'));
        }

        $endpoint = Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id);
        $endpoint->addQuery('limit', $options['limit']);

        if (isset($options['before'])) {
            $endpoint->addQuery('before', $options['before'] instanceof Message ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $endpoint->addQuery('after', $options['after'] instanceof Message ? $options['after']->id : $options['after']);
        }
        if (isset($options['around'])) {
            $endpoint->addQuery('around', $options['around'] instanceof Message ? $options['around']->id : $options['around']);
        }

        return $this->http->get($endpoint)->then(function ($responses) {
            $messages = new Collection();

            foreach ($responses as $response) {
                if (! $message = $this->messages->get('id', $response->id)) {
                    $message = $this->factory->create(Message::class, $response, true);
                }
                $messages->push($message);
            }

            return $messages;
        });
    }

    /**
     * Adds a message to the channels pinboard.
     *
     * @param Message $message The message to pin.
     *
     * @return ExtendedPromiseInterface
     */
    public function pinMessage(Message $message): ExtendedPromiseInterface
    {
        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->manage_messages) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to pin messages in the specified channel.'));
            }
        }

        if ($message->pinned) {
            return \React\Promise\reject(new \Exception('This message is already pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return \React\Promise\reject(new \Exception('You cannot pin a message to a different channel.'));
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_PIN, $this->id, $message->id))->then(function () use (&$message) {
            $message->pinned = true;

            return $message;
        });
    }

    /**
     * Removes a message from the channels pinboard.
     *
     * @param Message $message The message to un-pin.
     *
     * @return ExtendedPromiseInterface
     */
    public function unpinMessage(Message $message): ExtendedPromiseInterface
    {
        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->manage_messages) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to unpin messages in the specified channel.'));
            }
        }

        if (! $message->pinned) {
            return \React\Promise\reject(new \Exception('This message is not pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return \React\Promise\reject(new \Exception('You cannot un-pin a message from a different channel.'));
        }

        return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_PIN, $this->id, $message->id))->then(function () use (&$message) {
            $message->pinned = false;

            return $message;
        });
    }

    /**
     * Returns the channels invites.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function getInvites(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_INVITES, $this->id))->then(function ($response) {
            $invites = new Collection();

            foreach ($response as $invite) {
                $invite = $this->factory->create(Invite::class, $invite, true);
                $invites->push($invite);
            }

            return $invites;
        });
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

                $this->overwrites->push($this->factory->create(Overwrite::class, $overwrite, true));
            }
        }
    }

    /**
     * Sends a message to the channel if it is a text channel.
     *
     * @param string           $text             The text to send in the message.
     * @param bool             $tts              Whether the message should be sent with text to speech enabled.
     * @param Embed|array|null $embed            An embed to send.
     * @param array|null       $allowed_mentions Set mentions allowed in the message.
     * @param Message|null     $replyTo          The message to reply to.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function sendMessage(string $text, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): ExtendedPromiseInterface
    {
        if (! $this->allowText()) {
            return \React\Promise\reject(new \Exception('You can only send text messages to a text enabled channel.'));
        }

        if ($embed instanceof Embed) {
            $embed = $embed->getRawAttributes();
        }

        $content = [
            'content' => $text,
            'tts' => $tts,
            'embed' => $embed,
            'allowed_mentions' => $allowed_mentions,
        ];

        if (! is_null($replyTo)) {
            $content['message_reference'] = [
                'message_id' => $replyTo->id,
                'channel_id' => $replyTo->channel_id,
            ];
        }

        if (! $this->is_private && $member = $this->guild->members->offsetGet($this->discord->id)) {
            $botperms = $member->getPermissions($this);

            if (! $botperms->send_messages) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to send messages in the specified channel.'));
            }

            if ($tts && ! $botperms->send_tts_messages) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to send tts messages in the specified channel.'));
            }
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), $content)->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Edit a message in the channel.
     *
     * @param Message          $message The message to edit.
     * @param string           $text    The text to of the message.
     * @param bool             $tts     Whether the message should be sent with text to speech enabled.
     * @param Embed|array|null $embed   An embed to send.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function editMessage(Message $message, string $text, bool $tts = false, $embed = null): ExtendedPromiseInterface
    {
        if ($embed instanceof Embed) {
            $embed = $embed->getRawAttributes();
        }

        $content = [
            'content' => $text,
            'tts' => $tts,
            'embed' => $embed,
        ];

        return $this->http->patch(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->id, $message->id), $content)->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Sends an embed to the channel if it is a text channel.
     *
     * @param Embed $embed
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function sendEmbed(Embed $embed): ExtendedPromiseInterface
    {
        if (! $this->allowText()) {
            return \React\Promise\reject(new \Exception('You cannot send an embed to a voice channel.'));
        }

        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->send_messages) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to send messages in the specified channel.'));
            }
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), ['embed' => $embed->getRawAttributes()])->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Sends a file to the channel if it is a text channel.
     *
     * @param string      $filepath The path to the file to be sent.
     * @param string|null $filename The name to send the file as.
     * @param string|null $content  Message content to send with the file.
     * @param bool        $tts      Whether to send the message with TTS.
     *
     * @return ExtendedPromiseInterface
     */
    public function sendFile(string $filepath, ?string $filename = null, ?string $content = null, bool $tts = false): ExtendedPromiseInterface
    {
        if (! $this->allowText()) {
            return \React\Promise\reject(new \Exception('You cannot send a file to a voice channel.'));
        }

        if (! $this->is_private) {
            $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions($this);

            if (! $botperms->attach_files) {
                return \React\Promise\reject(new NoPermissionsException('You do not have permission to send files into the specified channel.'));
            }
        }

        if (! file_exists($filepath)) {
            return \React\Promise\reject(new FileNotFoundException("File does not exist at path {$filepath}."));
        }

        if (is_null($filename)) {
            $filename = basename($filepath);
        }

        $multipart = new Multipart([
            [
                'name' => 'file',
                'content' => file_get_contents($filepath),
                'filename' => $filename,
            ],
            [
                'name' => 'tts',
                'content' => $tts ? 'true' : 'false',
            ],
            [
                'name' => 'content',
                'content' => $content ?? '',
            ],
        ]);

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), (string) $multipart, $multipart->getHeaders())->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return ExtendedPromiseInterface
     */
    public function broadcastTyping(): ExtendedPromiseInterface
    {
        if (! $this->allowText()) {
            return \React\Promise\reject(new \Exception('You cannot broadcast typing to a voice channel.'));
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_TYPING, $this->id));
    }

    /**
     * Creates a message collector for the channel.
     *
     * @param callable $filter  The filter function. Returns true or false.
     * @param array    $options
     * @param int      $options ['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options ['limit'] The amount of messages allowed or false.
     *
     * @return ExtendedPromiseInterface
     */
    public function createMessageCollector(callable $filter, array $options = []): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $messages = new Collection([], null, null);
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
     * Returns if allow text.
     *
     * @return bool if we can send text or not.
     */
    public function allowText()
    {
        return in_array($this->type, [self::TYPE_TEXT, self::TYPE_DM, self::TYPE_GROUP, self::TYPE_NEWS]);
    }

    /**
     * Returns if allow voice.
     *
     * @return bool if we can send voice or not.
     */
    public function allowVoice()
    {
        return in_array($this->type, [self::TYPE_VOICE, self::TYPE_STAGE_CHANNEL]);
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
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
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'position' => $this->position,
            'topic' => $this->topic,
            'nsfw' => $this->nsfw,
            'rate_limit_per_user' => $this->rate_limit_per_user,
            'bitrate' => $this->bitrate,
            'user_limit' => $this->user_limit,
            'parent_id' => $this->parent_id,
            'rtc_region' => $this->rtc_region,
            'video_quality_mode' => $this->video_quality_mode,
            'permission_overwrites' => array_values($this->overwrites->map(function (Overwrite $overwrite) {
                return $overwrite->getUpdatableAttributes();
            })->toArray()),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'channel_id' => $this->id,
        ];
    }
    
    /**
     * Returns a formatted mention for text channel or name of the channel.
     *
     * @return string A formatted mention for text channel or name of the channel.
     */
    public function __toString(): string
    {
        return "<#{$this->id}>";
    }
}

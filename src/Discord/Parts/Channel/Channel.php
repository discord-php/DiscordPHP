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
use Discord\Builders\MessageBuilder;
use Discord\Exceptions\InvalidOverwriteException;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
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
use Discord\Parts\Channel\Forum\Reaction;
use Discord\Parts\Channel\Forum\Tag;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\Thread\Thread;
use Discord\Repository\Channel\InviteRepository;
use Discord\Repository\Channel\StageInstanceRepository;
use Discord\Repository\Channel\ThreadRepository;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

use function Discord\getSnowflakeTimestamp;
use function Discord\nowait;
use function React\Promise\all;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 *
 * @link https://discord.com/developers/docs/resources/channel#channel-object
 *
 * @since 2.0.0 Refactored as Part
 * @since 1.0.0
 *
 * @property      string              $id                                 The unique identifier of the Channel.
 * @property      int                 $type                               The type of the channel.
 * @property      string|null         $guild_id                           The unique identifier of the guild that the channel belongs to. Only for text or voice channels.
 * @property-read Guild|null          $guild                              The guild that the channel belongs to. Only for text or voice channels.
 * @property      int|null            $position                           The position of the channel on the sidebar.
 * @property      OverwriteRepository $overwrites                         Permission overwrites
 * @property      ?string|null        $name                               The name of the channel.
 * @property      ?string|null        $topic                              The topic of the channel (0-4096 characters for forum channels, 0-1024 characters for all others).
 * @property      bool|null           $nsfw                               Whether the channel is NSFW.
 * @property      ?string|null        $last_message_id                    The unique identifier of the last message sent in the channel (or thread for forum channels) (may not point to an existing or valid message or thread).
 * @property      int|null            $bitrate                            The bitrate of the channel. Only for voice channels.
 * @property      int|null            $user_limit                         The user limit of the channel. Max 99 for voice channels and 10000 for stage channels (0 refers to no limit).
 * @property      int|null            $rate_limit_per_user                Amount of seconds a user has to wait before sending a new message (slow mode).
 * @property      Collection|User[]   $recipients                         A collection of all the recipients in the channel. Only for DM or group channels.
 * @property-read User|null           $recipient                          The first recipient of the channel. Only for DM or group channels.
 * @property-read string|null         $recipient_id                       The ID of the recipient of the channel, if it is a DM channel.
 * @property      ?string|null        $icon                               Icon hash.
 * @property      string|null         $owner_id                           The ID of the DM creator. Only for DM or group channels.
 * @property      string|null         $application_id                     ID of the group DM creator if it is a bot.
 * @property      bool|null           $managed                            Whether the channel is managed by an application via the `gdm.join` OAuth2 scope. Only for group DM channels.
 * @property      ?string|null        $parent_id                          ID of the parent channel.
 * @property      Carbon|null         $last_pin_timestamp                 When the last message was pinned.
 * @property      ?string|null        $rtc_region                         Voice region id for the voice channel, automatic when set to null.
 * @property      int|null            $video_quality_mode                 The camera video quality mode of the voice channel, 1 when not present.
 * @property      int|null            $default_auto_archive_duration      Default duration for newly created threads, in minutes, to automatically archive the thread after recent activity, can be set to: 60, 1440, 4320, 10080.
 * @property      string|null         $permissions                        Computed permissions for the invoking user in the channel, including overwrites, only included when part of the resolved data received on an application command interaction.
 * @property      int|null            $flags                              Channel flags combined as a bitfield.
 * @property      Collection|Tag[]    $available_tags                     Set of tags that can be used in a forum channel, limited to 20.
 * @property      ?Reaction|null      $default_reaction_emoji             Emoji to show in the add reaction button on a thread in a forum channel.
 * @property      int|null            $default_thread_rate_limit_per_user The initial rate_limit_per_user to set on newly created threads in a forum channel. this field is copied to the thread at creation time and does not live update.
 * @property      ?int|null           $default_sort_order                 The default sort order type used to order posts in forum channels.
 * @property      int|null            $default_forum_layout               The default layout type used to display posts in a forum channel. Defaults to `0`, which indicates a layout view has not been set by a channel admin.
 *
 * @property bool                    $is_private      Whether the channel is a private channel.
 * @property MemberRepository        $members         Voice channel only - members in the channel.
 * @property MessageRepository       $messages        Text channel only - messages sent in the channel.
 * @property WebhookRepository       $webhooks        Webhooks in the channel.
 * @property ThreadRepository        $threads         Threads that belong to the channel.
 * @property InviteRepository        $invites         Invites in the channel.
 * @property StageInstanceRepository $stage_instances Stage instances in the channel.
 *
 * @method ExtendedPromiseInterface<Message> sendMessage(MessageBuilder $builder)
 */
class Channel extends Part
{
    public const TYPE_GUILD_TEXT = 0;
    public const TYPE_DM = 1;
    public const TYPE_GUILD_VOICE = 2;
    public const TYPE_GROUP_DM = 3;
    public const TYPE_GUILD_CATEGORY = 4;
    public const TYPE_GUILD_ANNOUNCEMENT = 5;
    public const TYPE_ANNOUNCEMENT_THREAD = 10;
    public const TYPE_PUBLIC_THREAD = 11;
    public const TYPE_PRIVATE_THREAD = 12;
    public const TYPE_GUILD_STAGE_VOICE = 13;
    public const TYPE_GUILD_DIRECTORY = 14;
    public const TYPE_GUILD_FORUM = 15;

    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_TEXT` */
    public const TYPE_TEXT = self::TYPE_GUILD_TEXT;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_VOICE` */
    public const TYPE_VOICE = self::TYPE_GUILD_VOICE;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GROUP_DM` */
    public const TYPE_GROUP = self::TYPE_GROUP_DM;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_CATEGORY` */
    public const TYPE_CATEGORY = self::TYPE_GUILD_CATEGORY;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_ANNOUNCEMENT` */
    public const TYPE_NEWS = self::TYPE_GUILD_ANNOUNCEMENT;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_ANNOUNCEMENT` */
    public const TYPE_ANNOUNCEMENT = self::TYPE_GUILD_ANNOUNCEMENT;
    /** @deprecated 10.0.0 Use `Channel::TYPE_ANNOUNCEMENT_THREAD` */
    public const TYPE_NEWS_THREAD = self::TYPE_ANNOUNCEMENT_THREAD;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_STAGE_VOICE` */
    public const TYPE_STAGE_CHANNEL = self::TYPE_GUILD_STAGE_VOICE;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_DIRECTORY` */
    public const TYPE_DIRECTORY = self::TYPE_GUILD_DIRECTORY;
    /** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_FORUM` */
    public const TYPE_FORUM = self::TYPE_GUILD_FORUM;

    public const VIDEO_QUALITY_AUTO = 1;
    public const VIDEO_QUALITY_FULL = 2;

    /** @deprecated 10.0.0 Use `Thread::FLAG_PINNED` */
    public const FLAG_PINNED = (1 << 1);
    public const FLAG_REQUIRE_TAG = (1 << 4);

    public const SORT_ORDER_LATEST_ACTIVITY = 0;
    public const SORT_ORDER_CREATION_DATE = 1;

    public const FORUM_LAYOUT_NOT_SET = 0;
    public const FORUM_LAYOUT_LIST_VIEW = 1;
    public const FORUM_LAYOUT_GRID_VIEW = 2;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'guild_id',
        'position',
        'name',
        'topic',
        'nsfw',
        'last_message_id',
        'bitrate',
        'user_limit',
        'rate_limit_per_user',
        'recipients',
        'icon',
        'owner_id',
        'application_id',
        'managed',
        'parent_id',
        'last_pin_timestamp',
        'rtc_region',
        'video_quality_mode',
        'default_auto_archive_duration',
        'permissions',
        'flags',
        'available_tags',
        'default_reaction_emoji',
        'default_thread_rate_limit_per_user',
        'default_sort_order',
        'default_forum_layout',

        // @internal
        'is_private',

        // repositories
        'permission_overwrites',
    ];

    /**
     * {@inheritDoc}
     */
    protected $repositories = [
        'overwrites' => OverwriteRepository::class,
        'members' => MemberRepository::class,
        'messages' => MessageRepository::class,
        'webhooks' => WebhookRepository::class,
        'threads' => ThreadRepository::class,
        'invites' => InviteRepository::class,
        'stage_instances' => StageInstanceRepository::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected function afterConstruct(): void
    {
        if (! array_key_exists('bitrate', $this->attributes) && $this->isVoiceBased()) {
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
        return in_array($this->type, [self::TYPE_DM, self::TYPE_GROUP_DM]);
    }

    /**
     * Gets the recipient attribute.
     *
     * @return User|null The recipient.
     */
    protected function getRecipientAttribute(): ?User
    {
        return $this->recipients->first();
    }

    /**
     * Gets the recipient ID attribute.
     *
     * @return string|null
     */
    protected function getRecipientIdAttribute(): ?string
    {
        if ($recipient = $this->recipient) {
            return $recipient->id;
        }

        return null;
    }

    /**
     * Gets the recipients attribute.
     *
     * @return Collection A collection of recepients.
     */
    protected function getRecipientsAttribute(): Collection
    {
        $recipients = Collection::for(User::class);

        foreach ($this->attributes['recipients'] ?? [] as $recipient) {
            $recipients->pushItem($this->discord->users->get('id', $recipient->id) ?: $this->factory->part(User::class, (array) $recipient, true));
        }

        return $recipients;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the last pinned message timestamp.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getLastPinTimestampAttribute(): ?Carbon
    {
        if (! isset($this->attributes['last_pin_timestamp'])) {
            return null;
        }

        return Carbon::parse($this->attributes['last_pin_timestamp']);
    }

    /**
     * Returns the channels pinned messages.
     *
     * @link https://discord.com/developers/docs/resources/channel#get-pinned-messages
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     */
    public function getPinnedMessages(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_PINS, $this->id))
        ->then(function ($responses) {
            $messages = Collection::for(Message::class);

            foreach ($responses as $response) {
                $messages->pushItem($this->messages->get('id', $response->id) ?: $this->messages->create($response, true));
            }

            return $messages;
        });
    }

    /**
     * Sets permissions in a channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#edit-channel-permissions
     *
     * @param Part        $part   A role or member.
     * @param array       $allow  An array of permissions to allow.
     * @param array       $deny   An array of permissions to deny.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws InvalidOverwriteException
     *
     * @return ExtendedPromiseInterface
     */
    public function setPermissions(Part $part, array $allow = [], array $deny = [], ?string $reason = null): ExtendedPromiseInterface
    {
        if ($part instanceof Member) {
            $type = Overwrite::TYPE_MEMBER;
        } elseif ($part instanceof Role) {
            $type = Overwrite::TYPE_ROLE;
        } else {
            return reject(new InvalidOverwriteException('Given part was not one of member or role.'));
        }

        $allow = array_fill_keys($allow, true);
        $deny = array_fill_keys($deny, true);

        $allowPart = $this->factory->part(ChannelPermission::class, $allow, $this->created);
        $denyPart = $this->factory->part(ChannelPermission::class, $deny, $this->created);

        $overwrite = $this->factory->part(Overwrite::class, [
            'id' => $part->id,
            'channel_id' => $this->id,
            'type' => $type,
            'allow' => $allowPart->bitwise,
            'deny' => $denyPart->bitwise,
        ], $this->created);

        return $this->setOverwrite($part, $overwrite, $reason);
    }

    /**
     * Sets an overwrite to the channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#edit-channel-permissions
     *
     * @param Part        $part      A role or member.
     * @param Overwrite   $overwrite An overwrite object.
     * @param string|null $reason    Reason for Audit Log.
     *
     * @throws NoPermissionsException    Missing manage_roles permission.
     * @throws InvalidOverwriteException Overwrite type is not member or role.
     *
     * @return ExtendedPromiseInterface
     */
    public function setOverwrite(Part $part, Overwrite $overwrite, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($this->guild_id && $botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_roles) {
                return reject(new NoPermissionsException("You do not have permission to manage roles in the channel {$this->id}."));
            }
        }

        if ($part instanceof Member) {
            $type = Overwrite::TYPE_MEMBER;
        } elseif ($part instanceof Role) {
            $type = Overwrite::TYPE_ROLE;
        } else {
            return reject(new InvalidOverwriteException('Given part was not one of member or role.'));
        }

        $payload = [
            'id' => $part->id,
            'type' => $type,
            'allow' => (string) $overwrite->allow->bitwise,
            'deny' => (string) $overwrite->deny->bitwise,
        ];

        if (! $this->created) {
            $this->attributes['permission_overwrites'][] = $payload;

            return resolve();
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_PERMISSIONS, $this->id, $part->id), $payload, $headers);
    }

    /**
     * Change category of a channel.
     *
     * @param Channel|string|null $category The category channel to set it to (either a Channel part or the channel ID or null for none).
     * @param int|null            $position The new channel position, not relative to category.
     * @param string|null         $reason   Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<self>
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws NoPermissionsException    Missing manage_channels permission in either channel.
     */
    public function setCategory($category, ?int $position = null, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! in_array($this->type, [self::TYPE_GUILD_TEXT, self::TYPE_GUILD_VOICE, self::TYPE_GUILD_ANNOUNCEMENT, self::TYPE_GUILD_FORUM])) {
            return reject(new \RuntimeException('You can only move Text, Voice, Announcement or Forum channel type.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_channels) {
                return reject(new NoPermissionsException("You do not have permission to manage the channel {$this->id}."));
            }
        }

        if (is_string($category)) {
            if ($cachedCategory = $this->guild->channels->get('id', $category)) {
                $category = $cachedCategory;
            }
        }
        if ($category instanceof Channel) {
            if ($category->type !== self::TYPE_GUILD_CATEGORY) {
                return reject(new \InvalidArgumentException('You can only move channel into a category.'));
            }

            if ($botperms = $category->getBotPermissions()) {
                if (! $botperms->manage_channels) {
                    return reject(new NoPermissionsException("You do not have permission to manage the category channel {$category->id}."));
                }
            }

            $category = $category->id;
        }

        $payload = ['parent_id' => $category];
        if (null !== $position) {
            $payload['position'] = $position;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::CHANNEL, $this->id), $payload, $headers)->then(function ($response) {
            $this->parent_id = $response->parent_id;
            $this->position = $response->position;

            return $this;
        });
    }

    /**
     * Moves a member to another voice channel.
     *
     * @param Member|string $member The member to move. (either a Member part or the member ID)
     * @param string|null   $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing move_members permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function moveMember($member, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $this->isVoiceBased()) {
            return reject(new \RuntimeException('You cannot move a member in a text channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->move_members) {
                return reject(new NoPermissionsException("You do not have permission to move members in the channel {$this->id}."));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['channel_id' => $this->id], $headers);
    }

    /**
     * Mutes a member on a voice channel.
     *
     * @param Member|string $member The member to mute. (either a Member part or the member ID)
     * @param string|null   $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing mute_members permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function muteMember($member, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $this->isVoiceBased()) {
            return reject(new \RuntimeException('You cannot mute a member in a text channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->mute_members) {
                return reject(new NoPermissionsException("You do not have permission to mute members in the channel {$this->id}."));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['mute' => true], $headers);
    }

    /**
     * Unmutes a member on a voice channel.
     *
     * @param Member|string $member The member to unmute. (either a Member part or the member ID)
     * @param string|null   $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing mute_members permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function unmuteMember($member, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $this->isVoiceBased()) {
            return reject(new \RuntimeException('You cannot unmute a member in a text channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->mute_members) {
                return reject(new NoPermissionsException("You do not have permission to unmute members in the channel {$this->id}."));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['mute' => false], $headers);
    }

    /**
     * Creates an invite for the channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-channel-invite
     *
     * @param array       $options                          An array of options. All fields are optional.
     * @param int         $options['max_age']               The time that the invite will be valid in seconds.
     * @param int         $options['max_uses']              The amount of times the invite can be used.
     * @param bool        $options['temporary']             Whether the invite is for temporary membership.
     * @param bool        $options['unique']                Whether the invite code should be unique (useful for creating many unique one time use invites).
     * @param int         $options['target_type']           The type of target for this voice channel invite.
     * @param string      $options['target_user_id']        The id of the user whose stream to display for this invite, required if target_type is `Invite::TARGET_TYPE_STREAM`, the user must be streaming in the channel.
     * @param string      $options['target_application_id'] The id of the embedded application to open for this invite, required if target_type is `Invite::TARGET_TYPE_EMBEDDED_APPLICATION`, the application must have the EMBEDDED flag.
     * @param string|null $reason                           Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing create_instant_invite permission.
     *
     * @return ExtendedPromiseInterface<Invite>
     */
    public function createInvite($options = [], ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $this->canInvite()) {
            return reject(new \RuntimeException('You cannot create invite in this type of channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->create_instant_invite) {
                return reject(new NoPermissionsException("You do not have permission to create instant invite in the channel {$this->id}."));
            }
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'max_age',
                'max_uses',
                'temporary',
                'unique',
                'target_type',
                'target_user_id',
                'target_application_id',
            ])
            ->setAllowedTypes('max_age', 'int')
            ->setAllowedTypes('max_uses', 'int')
            ->setAllowedTypes('temporary', 'bool')
            ->setAllowedTypes('unique', 'bool')
            ->setAllowedTypes('target_type', 'int')
            ->setAllowedTypes('target_user_id', ['string', 'int'])
            ->setAllowedTypes('target_application_id', ['string', 'int'])
            ->setAllowedValues('max_age', fn ($value) => ($value >= 0 && $value <= 604800))
            ->setAllowedValues('max_uses', fn ($value) => ($value >= 0 && $value <= 100));

        $options = $resolver->resolve($options);

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_INVITES, $this->id), $options, $headers)
            ->then(function ($response) {
                /** @var ?Invite */
                if (! $invitePart = $this->invites->get('code', $response->code)) {
                    /** @var Invite */
                    $invitePart = $this->invites->create($response, true);
                    $this->invites->pushItem($invitePart);
                }

                return $invitePart;
            });
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @link https://discord.com/developers/docs/resources/channel#bulk-delete-messages
     *
     * @param array|Traversable $messages An array of messages to delete.
     * @param string|null       $reason   Reason for Audit Log (only for bulk messages).
     *
     * @throws \InvalidArgumentException
     * @throws NoPermissionsException    Missing manage_messages permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteMessages($messages, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! is_array($messages) && ! ($messages instanceof Traversable)) {
            return reject(new \InvalidArgumentException('$messages must be an array or implement Traversable.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete messages in the channel {$this->id}."));
            }
        }

        $headers = $promises = $messagesBulk = $messagesSingle = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $message = $message->id;
            }

            if ($this->is_private || getSnowflakeTimestamp($message) < time() - 1209600) {
                $messagesSingle[] = $message;
            } else {
                $messagesBulk[] = $message;
            }
        }

        while (count($messagesBulk) > 1) {
            $promises[] = $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_BULK_DELETE, $this->id), ['messages' => array_slice($messagesBulk, 0, 100)], $headers);
            $messagesBulk = array_slice($messagesBulk, 100);
        }

        $messagesSingle = array_merge($messagesSingle, $messagesBulk);

        foreach ($messagesSingle as $message) {
            $promises[] = $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->id, $message));
        }

        return all($promises);
    }

    /**
     * Deletes a given number of messages, in order of time sent.
     *
     * @link https://discord.com/developers/docs/resources/channel#bulk-delete-messages
     *
     * @param int         $value
     * @param string|null $reason Reason for Audit Log (only for bulk messages).
     *
     * @throws NoPermissionsException Missing manage_messages permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function limitDelete(int $value, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete messages in the channel {$this->id}."));
            }
        }

        return $this->getMessageHistory(['limit' => $value, 'cache' => false])->then(function ($messages) use ($reason) {
            return $this->deleteMessages($messages, $reason);
        });
    }

    /**
     * Fetches message history.
     *
     * @link https://discord.com/developers/docs/resources/channel#get-channel-messages
     *
     * @param array               $options           Array of options.
     * @param string|Message|null $options['around'] Get messages around this message ID.
     * @param string|Message|null $options['before'] Get messages before this message ID.
     * @param string|Message|null $options['after']  Get messages after this message ID.
     * @param int|null            $options['limit']  Max number of messages to return (1-100). Defaults to 50.
     *
     * @throws NoPermissionsException Missing `read_message_history` permission.
     *                                Or also missing `connect` permission for text in voice.
     * @throws \RangeException
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     *
     * @todo Make it in a trait along with Thread
     */
    public function getMessageHistory(array $options = []): ExtendedPromiseInterface
    {
        if (! $this->is_private && $botperms = $this->getBotPermissions()) {
            if (! $botperms->read_message_history) {
                return reject(new NoPermissionsException("You do not have permission to read message history in the channel {$this->id}."));
            }

            if ($this->type == self::TYPE_GUILD_VOICE && ! $botperms->connect) {
                return reject(new NoPermissionsException("You do not have permission to connect in the channel {$this->id}."));
            }
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(['limit' => 50, 'cache' => true]);
        $resolver->setDefined(['before', 'after', 'around']);
        $resolver->setAllowedTypes('before', [Message::class, 'string']);
        $resolver->setAllowedTypes('after', [Message::class, 'string']);
        $resolver->setAllowedTypes('around', [Message::class, 'string']);
        $resolver->setAllowedTypes('limit', 'integer');
        $resolver->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after']) ||
            isset($options['before'], $options['around']) ||
            isset($options['around'], $options['after'])) {
            return reject(new \RangeException('Can only specify one of before, after and around.'));
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
            $messages = Collection::for(Message::class);

            foreach ($responses as $response) {
                $messages->pushItem($this->messages->get('id', $response->id) ?: $this->messages->create($response, true));
            }

            return $messages;
        });
    }

    /**
     * Adds a message to the channels pinboard.
     *
     * @link https://discord.com/developers/docs/resources/channel#pin-message
     *
     * @param Message     $message The message to pin.
     * @param string|null $reason  Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_messages permission.
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function pinMessage(Message $message, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $this->is_private && $botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to pin messages in the channel {$this->id}."));
            }
        }

        if ($message->pinned) {
            return reject(new \RuntimeException('This message is already pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return reject(new \RuntimeException('You cannot pin a message to a different channel.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_PIN, $this->id, $message->id), null, $headers)->then(function () use (&$message) {
            $message->pinned = true;

            return $message;
        });
    }

    /**
     * Removes a message from the channels pinboard.
     *
     * @link https://discord.com/developers/docs/resources/channel#unpin-message
     *
     * @param Message     $message The message to un-pin.
     * @param string|null $reason  Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_messages permission.
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface
     */
    public function unpinMessage(Message $message, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $this->is_private && $botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to unpin messages in the channel {$this->id}."));
            }
        }

        if (! $message->pinned) {
            return reject(new \RuntimeException('This message is not pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return reject(new \RuntimeException('You cannot un-pin a message from a different channel.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_PIN, $this->id, $message->id), null, $headers)->then(function () use (&$message) {
            $message->pinned = false;

            return $message;
        });
    }

    /**
     * Sets the permission overwrites attribute.
     *
     * @param ?array $overwrites
     */
    protected function setPermissionOverwritesAttribute(?array $overwrites): void
    {
        if ($overwrites) {
            $overwritesDiscrim = $this->overwrites->discrim;
            foreach ($overwrites as $overwrite) {
                $overwrite = (array) $overwrite;
                /** @var ?Overwrite */
                if ($overwritePart = $this->overwrites->offsetGet($overwrite[$overwritesDiscrim])) {
                    $overwritePart->fill($overwrite);
                } else {
                    /** @var Overwrite */
                    $overwritePart = $this->overwrites->create($overwrite, $this->created);
                    $overwritePart->created = &$this->created;
                }
                $this->overwrites->pushItem($overwritePart);
            }
        } else {
            if (null === nowait($this->overwrites->cache->clear())) {
                foreach ($this->overwrites->keys() as $key) {
                    $this->overwrites->offsetUnset($key);
                }
            }
        }

        $this->attributes['permission_overwrites'] = $overwrites;
    }

    /**
     * Gets the permission overwrites attribute.
     *
     * @param ?array $overwrites
     */
    protected function getPermissionOverwritesAttribute(): ?array
    {
        $overwrites = null;

        /** @var Overwrite */
        foreach ($this->overwrites as $overwrite) {
            $overwrites[] = $overwrite->getRawAttributes();
        }

        return $overwrites ?? $this->attributes['permission_overwrites'] ?? null;
    }

    /**
     * Gets the available tags attribute.
     *
     * @return Collection|Tag[] Available forum tags.
     *
     * @since 7.4.0
     */
    protected function getAvailableTagsAttribute(): Collection
    {
        $available_tags = Collection::for(Tag::class);

        foreach ($this->attributes['available_tags'] ?? [] as $available_tag) {
            $available_tags->pushItem($this->createOf(Tag::class, $available_tag));
        }

        return $available_tags;
    }

    /**
     * Gets the default reaction emoji attribute.
     *
     * @return Reaction|null Default forum reaction emoji.
     *
     * @since 7.4.0
     */
    protected function getDefaultReactionEmojiAttribute(): ?Reaction
    {
        if (! isset($this->attributes['default_reaction_emoji'])) {
            return null;
        }

        return $this->createOf(Reaction::class, $this->attributes['default_reaction_emoji']);
    }

    /**
     * Starts a thread in the channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#start-thread-without-message
     * @link https://discord.com/developers/docs/resources/channel#start-thread-in-forum-channel
     *
     * @param array          $options                          Thread params.
     * @param bool           $options['private']               Whether the thread should be private. Cannot start a private thread in a news channel channel. Ignored in forum channel.
     * @param string         $options['name']                  The name of the thread.
     * @param int|null       $options['auto_archive_duration'] Number of minutes of inactivity until the thread is auto-archived. one of 60, 1440, 4320, 10080.
     * @param bool|null      $options['invitable']             Whether non-moderators can add other non-moderators to a thread; only available when creating a private thread.
     * @param ?int|null      $options['rate_limit_per_user']   Amount of seconds a user has to wait before sending another message (0-21600).
     * @param MessageBuilder $options['message']               Contents of the first message in the forum thread.
     * @param string[]|null  $options['applied_tags']          The IDs of the set of tags that have been applied to a thread in a forum channel, limited to 5.
     * @param string|null    $reason                           Reason for Audit Log.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing various permissions:
     *                                create_private_threads when creating a private thread.
     *                                create_public_threads when creating a public thread.
     *                                send_messages when creating a forum post.
     *
     * @return ExtendedPromiseInterface<Thread>
     *
     * @since 10.0.0 Arguments for `$name`, `$private` and `$auto_archive_duration` are now inside `$options`
     */
    public function startThread(array|string $options, string|null|bool $reason = null, int $_auto_archive_duration = 1440, ?string $_reason = null): ExtendedPromiseInterface
    {
        // Old v7 signature
        if (is_string($options)) {
            $options = [
                'name' => $options,
                'auto_archive_duration' => $_auto_archive_duration,
            ];
            if (is_bool($reason)) {
                $options['private'] = $reason;
            }
            $reason = $_reason;
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'name',
                'auto_archive_duration',
                'rate_limit_per_user',
            ])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('auto_archive_duration', 'int')
            ->setAllowedTypes('rate_limit_per_user', ['null', 'int'])
            ->setAllowedValues('auto_archive_duration', fn ($value) => in_array($value, [60, 1440, 4320, 10080]))
            ->setAllowedValues('rate_limit_per_user', fn ($value) => $value >= 0 && $value <= 21600)
            ->setRequired('name');

        $botperms = $this->getBotPermissions();

        if ($this->type == self::TYPE_GUILD_FORUM) {
            $resolver
                ->setDefined([
                    'message',
                    'applied_tags',
                ])
                ->setAllowedTypes('message', [MessageBuilder::class])
                ->setAllowedTypes('applied_tags', 'array')
                ->setRequired('message')
                ->setNormalizer('applied_tags', function ($options, $values) {
                    foreach ($values as &$value) {
                        if ($value instanceof Tag) {
                            $value = $value->id;
                        }
                    }

                    return $values;
                });

            $options = $resolver->resolve($options);

            if ($botperms && ! $botperms->send_messages) {
                return reject(new NoPermissionsException("You do not have permission to create forum posts in the channel {$this->id}."));
            }

            $options['type'] = self::TYPE_PUBLIC_THREAD;
        } else {
            $resolver
                ->setDefined([
                    'private',
                    'invitable',
                ])
                ->setAllowedTypes('private', 'bool')
                ->setAllowedTypes('invitable', 'bool')
                ->setDefaults(['private' => false]);

            $options = $resolver->resolve($options);

            if ($options['private']) {
                if ($botperms && ! $botperms->create_public_threads) {
                    return reject(new NoPermissionsException("You do not have permission to create public threads in the channel {$this->id}."));
                }
            } else {
                if ($botperms && ! $botperms->create_private_threads) {
                    return reject(new NoPermissionsException("You do not have permission to create private threads in the channel {$this->id}."));
                }
            }

            if ($this->type == self::TYPE_GUILD_ANNOUNCEMENT) {
                if ($options['private']) {
                    return reject(new \RuntimeException('You cannot start a private thread within a news channel.'));
                }

                $options['type'] = self::TYPE_ANNOUNCEMENT_THREAD;
            } elseif ($this->type == self::TYPE_GUILD_TEXT) {
                $options['type'] = $options['private'] ? self::TYPE_PRIVATE_THREAD : self::TYPE_PUBLIC_THREAD;
            } else {
                return reject(new \RuntimeException('You cannot start a thread in this type of channel.'));
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        unset($options['private']);

        return (function () use ($options, $headers) {
            if (isset($options['message']) && $options['message']->requiresMultipart()) {
                /** @var Multipart */
                $multipart = $options['message']->toMultipart(false);
                $multipart->add([
                    'name' => 'payload_json',
                    'content' => json_encode($options),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);

                return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_THREADS, $this->id), (string) $multipart, $multipart->getHeaders() + $headers);
            }

            return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_THREADS, $this->id), $options, $headers);
        })()->then(function ($response) {
            /** @var ?Thread */
            if ($threadPart = $this->threads->offsetGet($response->id)) {
                $threadPart->fill((array) $response);
            } else {
                /** @var Thread */
                $threadPart = $this->threads->create($response, true);
            }
            $this->threads->pushItem($threadPart);
            if ($messageId = ($response->message->id ?? null)) {
                /** @var ?Message */
                if (! $threadPart->messages->offsetExists($messageId)) {
                    // Don't store in the external cache
                    $messagePart = $threadPart->messages->create($response->message, true);
                    $threadPart->messages->offsetSet($messageId, $messagePart);
                }
            }

            return $threadPart;
        });
    }

    /**
     * Sends a message to the channel.
     *
     * Takes a `MessageBuilder` or content of the message for the first
     * parameter. If the first parameter is an instance of `MessageBuilder`, the
     * rest of the arguments are disregarded.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param MessageBuilder|string $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                  $tts              Whether the message is TTS.
     * @param Embed|array|null      $embed            An embed object or array to send in the message.
     * @param array|null            $allowed_mentions Allowed mentions object for the message.
     * @param Message|null          $replyTo          Sends the message as a reply to the given message instance.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing various permissions depending on the message body.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendMessage($message, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): ExtendedPromiseInterface
    {
        // Backwards compatible support for old `sendMessage` function signature.
        if (! ($message instanceof MessageBuilder)) {
            $message = MessageBuilder::new()
                ->setContent($message);

            if ($tts) {
                $message->setTts(true);
            }

            if ($embed) {
                $message->addEmbed($embed);
            }

            if ($allowed_mentions) {
                $message->setAllowedMentions($allowed_mentions);
            }

            if ($replyTo) {
                $message->setReplyTo($replyTo);
            }
        }

        if (! $this->isTextBased()) {
            return reject(new \RuntimeException('You can only send messages to text channels.'));
        }

        if (! $this->is_private && $botperms = $this->getBotPermissions()) {
            if (! $botperms->send_messages) {
                return reject(new NoPermissionsException("You do not have permission to send messages in the channel {$this->id}."));
            }

            if ($message->getTts() && ! $botperms->send_tts_messages) {
                return reject(new NoPermissionsException("You do not have permission to send tts messages in the channel {$this->id}."));
            }

            if ($message->numFiles() > 0 && ! $botperms->attach_files) {
                return reject(new NoPermissionsException("You do not have permission to send files in the channel {$this->id}."));
            }
        }

        return (function () use ($message) {
            if ($message->requiresMultipart()) {
                $multipart = $message->toMultipart();

                return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), $message);
        })()->then(function ($response) {
            if (! $messagePart = $this->messages->get('id', $response->id)) {
                $messagePart = $this->messages->create($response, true);
            }

            return $messagePart;
        });
    }

    /**
     * Sends an embed to the channel.
     *
     * @deprecated 10.0.0 Use `Channel::sendMessage` with `MessageBuilder::addEmbed()`
     *
     * @see Channel::sendMessage()
     *
     * @param Embed $embed Embed to send.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendEmbed(Embed $embed): ExtendedPromiseInterface
    {
        return $this->sendMessage(MessageBuilder::new()
            ->addEmbed($embed));
    }

    /**
     * Sends a file to the channel.
     *
     * @deprecated 7.0.0 Use `Channel::sendMessage` to send files.
     *
     * @see Channel::sendMessage()
     *
     * @param string      $filepath The path to the file to be sent.
     * @param string|null $filename The name to send the file as.
     * @param string|null $content  Message content to send with the file.
     * @param bool        $tts      Whether to send the message with TTS.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendFile(string $filepath, ?string $filename = null, ?string $content = null, bool $tts = false): ExtendedPromiseInterface
    {
        $builder = MessageBuilder::new()
            ->setTts($tts)
            ->addFile($filepath, $filename);

        if ($content) {
            $builder->setContent($content);
        }

        return $this->sendMessage($builder);
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @link https://discord.com/developers/docs/resources/channel#trigger-typing-indicator
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface
     */
    public function broadcastTyping(): ExtendedPromiseInterface
    {
        if (! $this->isTextBased()) {
            return reject(new \RuntimeException('You cannot broadcast typing to a voice channel.'));
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_TYPING, $this->id));
    }

    /**
     * Creates a message collector for the channel.
     *
     * @param callable $filter           The filter function. Returns true or false.
     * @param array    $options
     * @param int      $options['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options['limit'] The amount of messages allowed or false.
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
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
                $messages->pushItem($message);

                if ($options['limit'] !== false && count($messages) >= $options['limit']) {
                    $this->discord->removeListener(Event::MESSAGE_CREATE, $eventHandler);
                    $deferred->resolve($messages);

                    if (null !== $timer) {
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
     *
     * @deprecated 10.0.0 Use `Channel::isTextBased()`
     */
    public function allowText()
    {
        return $this->isTextBased();
    }

    /**
     * Returns if allow voice.
     *
     * @return bool if we can send voice or not.
     *
     * @deprecated 10.0.0 Use `Channel::isVoiceBased()`
     */
    public function allowVoice()
    {
        return $this->isVoiceBased();
    }

    /**
     * Returns if allow invite.
     *
     * @return bool if we can make invite or not.
     *
     * @deprecated 10.0.0 Use `Channel::canInvite()`
     */
    public function allowInvite()
    {
        return $this->canInvite();
    }

    /**
     * Returns if channel type is text based.
     *
     * @return bool Whether the channel is possible for sending text.
     */
    public function isTextBased()
    {
        return in_array($this->type, [
            self::TYPE_GUILD_TEXT,
            self::TYPE_DM,
            self::TYPE_GUILD_VOICE,
            self::TYPE_GROUP_DM,
            self::TYPE_PUBLIC_THREAD,
            self::TYPE_PRIVATE_THREAD,
            self::TYPE_GUILD_ANNOUNCEMENT,
            self::TYPE_GUILD_STAGE_VOICE,
        ]);
    }

    /**
     * Returns if channel type is voice based.
     *
     * @return bool Wether the channel is possible for voice.
     */
    public function isVoiceBased()
    {
        return in_array($this->type, [self::TYPE_GUILD_VOICE, self::TYPE_GUILD_STAGE_VOICE]);
    }

    /**
     * Returns if invite can be created in this type of channel.
     *
     * @return bool Whether the channel type is possible for creating invite.
     */
    public function canInvite()
    {
        return in_array($this->type, [self::TYPE_GUILD_TEXT, self::TYPE_GUILD_VOICE, self::TYPE_GUILD_ANNOUNCEMENT, self::TYPE_GUILD_STAGE_VOICE, self::TYPE_GUILD_FORUM]);
    }

    /**
     * Returns the bot's permissions in the channel.
     *
     * @return RolePermission|null
     */
    public function getBotPermissions(): ?RolePermission
    {
        if (! $guild = $this->guild) {
            return null;
        }

        return $guild->members->get('id', $this->discord->id)->getPermissions($this);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-channel-json-params
     */
    public function getCreatableAttributes(): array
    {
        // Required
        $attr = ['name' => $this->name];

        // Marked "Channel Type: All" in documentation
        $attr += $this->makeOptionalAttributes([
            'type' => $this->type,
            'permission_overwrites' => $this->getPermissionOverwritesAttribute(),
            'position' => $this->position,
        ]);

        if (null === $this->type) {
            // Type was not specified but we must not assume its default to GUILD_TEXT as that is determined by API
            $this->discord->getLogger()->warning('Not specifying channel type, creating with all filled attributes');
            $attr += $this->getRawAttributes(); // Send the remaining raw attributes

            return $attr;
        }

        switch ($this->type) {
            case self::TYPE_GUILD_TEXT:
                $attr += $this->makeOptionalAttributes([
                    'topic' => $this->topic,
                    'rate_limit_per_user' => $this->rate_limit_per_user,
                    'parent_id' => $this->parent_id,
                    'nsfw' => $this->nsfw,
                    'default_auto_archive_duration' => $this->default_auto_archive_duration,
                    'default_thread_rate_limit_per_user' => $this->default_thread_rate_limit_per_user,
                ]);
                break;

            case self::TYPE_GUILD_VOICE:
                $attr += $this->makeOptionalAttributes([
                    'bitrate' => $this->bitrate,
                    'user_limit' => $this->user_limit,
                    'rate_limit_per_user' => $this->rate_limit_per_user,
                    'parent_id' => $this->parent_id,
                    'nsfw' => $this->nsfw,
                    'rtc_region' => $this->rtc_region,
                    'video_quality_mode' => $this->video_quality_mode,
                ]);
                break;

            case self::TYPE_GUILD_ANNOUNCEMENT:
                $attr += $this->makeOptionalAttributes([
                    'topic' => $this->topic,
                    'parent_id' => $this->parent_id,
                    'nsfw' => $this->nsfw,
                    'default_auto_archive_duration' => $this->default_auto_archive_duration,
                ]);
                break;

            case self::TYPE_GUILD_STAGE_VOICE:
                $attr += $this->makeOptionalAttributes([
                    'bitrate' => $this->bitrate,
                    'user_limit' => $this->user_limit,
                    'rate_limit_per_user' => $this->rate_limit_per_user,
                    'parent_id' => $this->parent_id,
                    'nsfw' => $this->nsfw,
                    'rtc_region' => $this->rtc_region,
                    'video_quality_mode' => $this->video_quality_mode,
                ]);
                break;

            case self::TYPE_GUILD_FORUM:
                $attr += $this->makeOptionalAttributes([
                    'topic' => $this->topic,
                    'rate_limit_per_user' => $this->rate_limit_per_user,
                    'parent_id' => $this->parent_id,
                    'nsfw' => $this->nsfw,
                    'default_auto_archive_duration' => $this->default_auto_archive_duration,
                    'default_reaction_emoji' => $this->attributes['default_reaction_emoji'] ?? null,
                    'available_tags',
                    'default_sort_order' => $this->default_sort_order,
                    'default_forum_layout' => $this->default_forum_layout,
                    'default_thread_rate_limit_per_user' => $this->default_thread_rate_limit_per_user, // Canceled documentation #5606
                ]);
                break;
        }

        return $attr;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/channel#modify-channel-json-params-guild-channel
     */
    public function getUpdatableAttributes(): array
    {
        if ($this->type == self::TYPE_GROUP_DM) {
            return [
                'name' => $this->name,
                'icon' => $this->icon,
            ];
        }

        // Marked "Channel Type: All" in documentation
        $attr = [
            'name' => $this->name,
            'position' => $this->position,
            'permission_overwrites' => $this->getPermissionOverwritesAttribute(),
        ];

        switch ($this->type) {
            case self::TYPE_GUILD_TEXT:
                $attr['type'] = $this->type;
                $attr['topic'] = $this->topic;
                $attr['nsfw'] = $this->nsfw;
                $attr['rate_limit_per_user'] = $this->rate_limit_per_user;
                $attr['parent_id'] = $this->parent_id;
                $attr['default_auto_archive_duration'] = $this->default_auto_archive_duration;
                $attr += $this->makeOptionalAttributes([
                    'default_thread_rate_limit_per_user' => $this->default_thread_rate_limit_per_user,
                ]);
                break;

            case self::TYPE_GUILD_VOICE:
                $attr['nsfw'] = $this->nsfw;
                $attr['rate_limit_per_user'] = $this->rate_limit_per_user;
                $attr['bitrate'] = $this->bitrate;
                $attr['user_limit'] = $this->user_limit;
                $attr['parent_id'] = $this->parent_id;
                $attr['rtc_region'] = $this->rtc_region;
                $attr['video_quality_mode'] = $this->video_quality_mode;
                break;

            case self::TYPE_GUILD_ANNOUNCEMENT:
                $attr['type'] = $this->type;
                $attr['topic'] = $this->topic;
                $attr['nsfw'] = $this->nsfw;
                $attr['parent_id'] = $this->parent_id;
                $attr['default_auto_archive_duration'] = $this->default_auto_archive_duration;
                break;

            case self::TYPE_GUILD_STAGE_VOICE:
                $attr['nsfw'] = $this->nsfw;
                $attr['rate_limit_per_user'] = $this->rate_limit_per_user;
                $attr['bitrate'] = $this->bitrate;
                $attr['user_limit'] = $this->user_limit;
                $attr['parent_id'] = $this->parent_id;
                $attr['rtc_region'] = $this->rtc_region;
                $attr['video_quality_mode'] = $this->video_quality_mode;
                break;

            case self::TYPE_GUILD_FORUM:
                $attr['topic'] = $this->topic;
                $attr['nsfw'] = $this->nsfw;
                $attr['rate_limit_per_user'] = $this->rate_limit_per_user;
                $attr['parent_id'] = $this->parent_id;
                $attr['default_auto_archive_duration'] = $this->default_auto_archive_duration;
                $attr += $this->makeOptionalAttributes([
                    'flags' => $this->flags,
                    'available_tags',
                    'default_reaction_emoji' => $this->attributes['default_reaction_emoji'],
                    'default_thread_rate_limit_per_user' => $this->default_thread_rate_limit_per_user,
                    'default_sort_order' => $this->default_sort_order,
                    'default_forum_layout' => $this->default_forum_layout,
                ]);
                break;
        }

        return $attr;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
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

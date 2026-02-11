<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Builders\MessageBuilder;
use Discord\Exceptions\InvalidOverwriteException;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Channel\MessageRepository;
use Discord\Repository\Channel\OverwriteRepository;
use Discord\Repository\Channel\WebhookRepository;
use Discord\Helpers\Multipart;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Forum\Reaction;
use Discord\Parts\Channel\Forum\Tag;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\Repository\Channel\InviteRepository;
use Discord\Repository\Channel\StageInstanceRepository;
use Discord\Repository\Channel\ThreadRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\PrivateChannelRepository;
use React\Promise\PromiseInterface;
use Stringable;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\nowait;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 *
 * @link https://discord.com/developers/docs/resources/channel#channel-object
 *
 * @todo Class will be abstract and deprecated for userland in v11.
 *
 * @since 2.0.0 Refactored as Part
 * @since 1.0.0
 *
 * @property      string                             $id                                 The id of this channel.
 * @property      int                                $type                               The type of channel.
 * @property      string|null                        $guild_id                           The id of the guild (may be missing for some channel objects received over gateway guild dispatches).
 * @property      int|null                           $position                           Sorting position of the channel.
 * @property      Overwrite[]                        $permission_overwrites              Explicit permission overwrites for members and roles.
 * @property      ?string|null                       $name                               The name of the channel (1-100 characters).
 * @property      ?string|null                       $topic                              The topic of the channel (0-4096 characters for forum/media, 0-1024 for others).
 * @property      bool|null                          $nsfw                               Whether the channel is NSFW.
 * @property      int|null                           $bitrate                            The bitrate (in bits) of the voice or stage channel; min 8000.
 * @property      int|null                           $user_limit                         The user limit of the voice or stage channel, max 99 for voice channels and 10,000 for stage channels (0 refers to no limit).
 * @property      ExCollectionInterface<User>|User[] $recipients                         The recipients of the DM.
 * @property-read User|null                          $recipient                          The first recipient of the DM (DM/group).
 * @property-read string|null                        $recipient_id                       The ID of the recipient (DM).
 * @property      ?string|null                       $icon                               Icon hash of the group DM.
 * @property      string|null                        $application_id                     Application id of the group DM creator if bot-created.
 * @property      bool|null                          $managed                            For group DM channels: whether the channel is managed by an application via the `gdm.join` OAuth2 scope.
 * @property      ?string|null                       $rtc_region                         Voice region id for the voice channel, automatic when set to null.
 * @property      int|null                           $video_quality_mode                 The camera video quality mode of the voice channel, 1 when not present.
 * @property      int|null                           $default_auto_archive_duration      Default duration for newly created threads, in minutes.
 * @property      string|null                        $permissions                        Computed permissions for the invoking user in the channel, including overwrites, only included when part of the `resolved` data received on an interaction. This does not include implicit permissions, which may need to be checked separately.
 * @property      int|null                           $flags                              Channel flags combined as a bitfield.
 * @property      ExCollectionInterface<Tag>|Tag[]   $available_tags                     The set of tags that can be used in a `GUILD_FORUM` or a `GUILD_MEDIA` channel. Limited to 20.
 * @property      ?Reaction|null                     $default_reaction_emoji             The emoji to show in the add reaction button on a thread in a `GUILD_FORUM` or a `GUILD_MEDIA` channel.
 * @property      int|null                           $default_thread_rate_limit_per_user The initial `rate_limit_per_user` to set on newly created threads in a channel. This field is copied to the thread at creation time and does not live update.
 * @property      ?int|null                          $default_sort_order                 The default sort order type used to order posts in `GUILD_FORUM` and `GUILD_MEDIA` channels. Defaults to `null`, which indicates a preferred sort order hasn't been set by a channel admin.
 * @property      int|null                           $default_forum_layout               The default forum layout view used to display posts in `GUILD_FORUM` channels. Defaults to `0`, which indicates a layout view has not been set by a channel admin.
 *
 * @property OverwriteRepository     $overwrites      Permission overwrites.
 * @property WebhookRepository       $webhooks        Webhooks in the channel.
 * @property ThreadRepository        $threads         Threads that belong to the channel.
 * @property InviteRepository        $invites         Invites in the channel.
 * @property StageInstanceRepository $stage_instances Stage instances in the channel.
 */
class Channel extends Part implements Stringable
{
    use ChannelTrait;

    /** A text channel within a server. */
    public const TYPE_GUILD_TEXT = 0;
    /** A direct message between users. */
    public const TYPE_DM = 1;
    /** A voice channel within a server. */
    public const TYPE_GUILD_VOICE = 2;
    /** A direct message between multiple users. */
    public const TYPE_GROUP_DM = 3;
    /** An organizational category that contains up to 50 channels. */
    public const TYPE_GUILD_CATEGORY = 4;
    /** A channel that users can follow and crosspost into their own server (formerly news channels). */
    public const TYPE_GUILD_ANNOUNCEMENT = 5;
    /** A temporary sub-channel within a `GUILD_ANNOUNCEMENT` channel. */
    public const TYPE_ANNOUNCEMENT_THREAD = 10;
    /** A temporary sub-channel within a `GUILD_TEXT` or `GUILD_FORUM` channel. */
    public const TYPE_PUBLIC_THREAD = 11;
    /** A temporary sub-channel within a `GUILD_TEXT` channel that is only viewable by those invited and those with the `MANAGE_THREADS` permission. */
    public const TYPE_PRIVATE_THREAD = 12;
    /** A voice channel for hosting events with an audience. */
    public const TYPE_GUILD_STAGE_VOICE = 13;
    /** The channel in a hub containing the listed servers. */
    public const TYPE_GUILD_DIRECTORY = 14;
    /** Channel that can only contain threads. */
    public const TYPE_GUILD_FORUM = 15;
    /** Channel that can only contain threads, similar to `GUILD_FORUM` channels. */
    public const TYPE_GUILD_MEDIA = 16;

    public const TYPES = [
        self::TYPE_GUILD_TEXT => GuildText::class,
        self::TYPE_DM => DM::class,
        self::TYPE_GUILD_VOICE => GuildVoice::class,
        self::TYPE_GROUP_DM => GroupDM::class,
        self::TYPE_GUILD_CATEGORY => GuildCategory::class,
        self::TYPE_GUILD_ANNOUNCEMENT => GuildAnnouncement::class,
        self::TYPE_ANNOUNCEMENT_THREAD => AnnouncementThread::class,
        self::TYPE_PUBLIC_THREAD => PublicThread::class,
        self::TYPE_PRIVATE_THREAD => PrivateThread::class,
        self::TYPE_GUILD_STAGE_VOICE => GuildStageVoice::class,
        self::TYPE_GUILD_DIRECTORY => GuildDirectory::class,
        self::TYPE_GUILD_FORUM => GuildForum::class,
        self::TYPE_GUILD_MEDIA => GuildMedia::class,
    ];

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

    /** Discord chooses the quality for optimal performance. */
    public const VIDEO_QUALITY_AUTO = 1;
    /** 720p. */
    public const VIDEO_QUALITY_FULL = 2;

    /** @deprecated 10.0.0 Use `Thread::FLAG_PINNED` */
    public const FLAG_PINNED = (1 << 1);
    /** Whether a tag is required to be specified when creating a thread in a `GUILD_FORUM` or a `GUILD_MEDIA` channel. Tags are specified in the `applied_tags` field. */
    public const FLAG_REQUIRE_TAG = (1 << 4);
    /** When set hides the embedded media download options. Available only for media channels. */
    public const HIDE_MEDIA_DOWNLOAD_OPTIONS = (1 << 15);

    /** Sort forum posts by activity. */
    public const SORT_ORDER_LATEST_ACTIVITY = 0;
    /**	Sort forum posts by creation time (from most recent to oldest). */
    public const SORT_ORDER_CREATION_DATE = 1;

    /** No default has been set for forum channel. */
    public const FORUM_LAYOUT_NOT_SET = 0;
    /** Display posts as a list. */
    public const FORUM_LAYOUT_LIST_VIEW = 1;
    /** Display posts as a collection of tiles. */
    public const FORUM_LAYOUT_GALLERY_VIEW = 2;
    /** @deprecated 10.36.32 Use `Channel::FORUM_LAYOUT_GALLERY_VIEW` */
    public const FORUM_LAYOUT_GRID_VIEW = self::FORUM_LAYOUT_GALLERY_VIEW;

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    protected $repositories = [
        'overwrites' => OverwriteRepository::class,
        'messages' => MessageRepository::class,
        'webhooks' => WebhookRepository::class,
        'threads' => ThreadRepository::class,
        'invites' => InviteRepository::class,
        'stage_instances' => StageInstanceRepository::class,
    ];

    /**
     * @inheritDoc
     */
    protected function afterConstruct(): void
    {
        if (! array_key_exists('bitrate', $this->attributes) && $this->isVoiceBased()) {
            $this->bitrate = 64000;
        }
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
     * @return ExCollectionInterface<User>|User[] A collection of recipients.
     */
    protected function getRecipientsAttribute(): ExCollectionInterface
    {
        /** @var ExCollectionInterface<User> $recipients */
        $recipients = $this->discord->getCollectionClass()::for(User::class);

        foreach ($this->attributes['recipients'] ?? [] as $recipient) {
            $recipients->pushItem($this->discord->users->get('id', $recipient->id) ?? $this->factory->part(User::class, (array) $recipient, true));
        }

        return $recipients;
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
     * @return PromiseInterface
     */
    public function setPermissions(Part $part, array $allow = [], array $deny = [], ?string $reason = null): PromiseInterface
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
     * @return PromiseInterface
     */
    public function setOverwrite(Part $part, Overwrite $overwrite, ?string $reason = null): PromiseInterface
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

            return resolve(null);
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
     * @return PromiseInterface<self>
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws NoPermissionsException    Missing manage_channels permission in either channel.
     */
    public function setCategory($category, ?int $position = null, ?string $reason = null): PromiseInterface
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
     * @return PromiseInterface<Member>
     */
    public function moveMember($member, ?string $reason = null): PromiseInterface
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
     * @throws \RuntimeException      Channel is not voice-based.
     * @throws NoPermissionsException Missing mute_members permission.
     *
     * @return PromiseInterface<Member>
     */
    public function muteMember($member, ?string $reason = null): PromiseInterface
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
     * @throws \RuntimeException      Channel is not voice-based.
     * @throws NoPermissionsException Missing mute_members permission.
     *
     * @return PromiseInterface<Member>
     */
    public function unmuteMember($member, ?string $reason = null): PromiseInterface
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
     * Deafens a member in the voice channel.
     *
     * @param Member|string $member The member to deafen. (either a Member part or the member ID)
     * @param string|null   $reason Reason for Audit Log.
     *
     * @throws \RuntimeException      Channel is not voice-based.
     * @throws NoPermissionsException Missing deafen_members permission.
     *
     * @return PromiseInterface<Member>
     */
    public function deafenMember($member, ?string $reason = null): PromiseInterface
    {
        if (! $this->isVoiceBased()) {
            return reject(new \RuntimeException('You cannot deafen a member in a text channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->deafen_members) {
                return reject(new NoPermissionsException("You do not have permission to deafen members in the channel {$this->id}."));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['deaf' => true], $headers);
    }

    /**
     * Undeafens a member in the voice channel.
     *
     * @param Member|string $member The member to undeafen. (either a Member part or the member ID)
     * @param string|null   $reason Reason for Audit Log.
     *
     * @throws \RuntimeException      Channel is not voice-based.
     * @throws NoPermissionsException Missing deafen_members permission.
     *
     * @return PromiseInterface<Member>
     */
    public function undeafenMember($member, ?string $reason = null): PromiseInterface
    {
        if (! $this->isVoiceBased()) {
            return reject(new \RuntimeException('You cannot deafen a member in a text channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->deafen_members) {
                return reject(new NoPermissionsException("You do not have permission to deafen members in the channel {$this->id}."));
            }
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $member), ['deaf' => false], $headers);
    }

    /**
     * Sends a soundboard sound to the voice channel.
     *
     * @param string      $sound_id        The ID of the soundboard sound to play.
     * @param string|null $source_guild_id The ID of the guild where the sound originates, if using an external sound.
     *
     * @throws \RuntimeException      If the channel is not voice-based, the bot is not connected to the correct voice channel,
     *                                or the bot is deafened, self-deafened, muted, or suppressed.
     * @throws NoPermissionsException If the bot lacks the required permissions to send soundboard sounds or use external sounds.
     *
     * @return PromiseInterface
     *
     * @since 10.11.3
     */
    public function sendSoundboardSound(string $sound_id, ?string $source_guild_id = null): PromiseInterface
    {
        if (! $this->isVoiceBased()) {
            return reject(new \RuntimeException('You cannot send soundboard sounds in a text channel.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->speak || ! $botperms->use_soundboard) {
                return reject(new NoPermissionsException("You do not have permission to send soundboard sounds in the channel {$this->id}."));
            }
        }

        if ($this->guild !== null) {
            if ($member = $this->guild->members->get('id', $this->discord->id)) {
                if (! $voiceChannel = $member->getVoiceChannel()) {
                    return reject(new \RuntimeException('Bot must be connected to a voice channel to send soundboard sounds.'));
                }
                if (! $voiceChannel->id === $this->id) {
                    return reject(new \RuntimeException("Bot must be connected to the voice channel {$this->id} to send it soundboard sounds."));
                }
                if ($member->deaf || $member->mute) { // Member can also not be self-muted or suppressed
                    return reject(new \RuntimeException('Bot must be connected to the voice channel and not deafened, muted, or suppressed to send soundboard sounds.'));
                }
            }
        }

        $payload = [
            'sound_id' => $sound_id,
        ];

        if ($source_guild_id !== null) {
            if ($botperms) {
                if ($this->guild_id !== $source_guild_id) {
                    if (! $botperms->use_external_sounds) {
                        return reject(new NoPermissionsException("You do not have permission to use external sounds in the channel {$this->id}."));
                    }
                }
            }
            $payload['source_guild_id'] = $source_guild_id;
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_SEND_SOUNDBOARD_SOUND, $this->id), $payload);
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
     * @param object      $options['target_users_file']     (TODO) A csv file with a single column of user IDs for all the users able to accept this invite. Requires `multipart/form-data` as the content type with other parameters as form fields in the multipart body. Uploading a file with invalid user IDs will result in a 400 with the invalid IDs described. Duplicate user IDs in the file will be ignored.
     * @param string      $options['payload_json']          JSON-encoded body of non-file params, only for `multipart/form-data` requests.
     * @param string[]    $options['role_ids']              The role ID(s) for roles in the guild given to the users that accept this invite. Requires the `MANAGE_ROLES` permission and cannot assign roles with higher permissions than the sender.
     * @param string|null $reason                           Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing create_instant_invite permission.
     *
     * @return PromiseInterface<Invite>
     */
    public function createInvite($options = [], ?string $reason = null): PromiseInterface
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
                'target_users_file',
                'payload_json',
                'role_ids',
            ])
            ->setAllowedTypes('max_age', 'int')
            ->setAllowedValues('max_age', fn ($value) => ($value >= 0 && $value <= 604800))
            ->setAllowedTypes('max_uses', 'int')
            ->setAllowedValues('max_uses', fn ($value) => ($value >= 0 && $value <= 100))
            ->setAllowedTypes('temporary', 'bool')
            ->setAllowedTypes('unique', 'bool')
            ->setAllowedTypes('target_type', 'int')
            ->setAllowedTypes('target_user_id', ['string', 'int'])
            ->setAllowedTypes('target_application_id', ['string', 'int'])
            ->setAllowedTypes('payload_json', 'string')
            ->setAllowedTypes('role_ids', 'array');

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
     * Deletes a given number of messages, in order of time sent.
     *
     * @link https://discord.com/developers/docs/resources/channel#bulk-delete-messages
     *
     * @param int         $value
     * @param string|null $reason Reason for Audit Log (only for bulk messages).
     *
     * @throws NoPermissionsException Missing manage_messages permission.
     *
     * @return PromiseInterface
     */
    public function limitDelete(int $value, ?string $reason = null): PromiseInterface
    {
        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete messages in the channel {$this->id}."));
            }
        }

        return $this->getMessageHistory(['limit' => $value, 'cache' => false])->then(fn ($messages) => $this->deleteMessages($messages, $reason));
    }

    /**
     * Delete a channel, or close a private message.
     *
     * Deleting a category does not delete its child channels; they will have their parent_id removed and a Channel Update Gateway event will fire for each of them.
     * For Community guilds, the Rules or Guidelines channel and the Community Updates channel cannot be deleted.
     *
     * @link https://discord.com/developers/docs/resources/channel#deleteclose-channel
     *
     * @param string|null $reason Reason for Audit Log.
     *
     * @return PromiseInterface<Channel>
     *
     * @throws NoPermissionsException Missing manage_channels permission.
     *
     * @since 10.35.0
     */
    public function delete(?string $reason = null): PromiseInterface
    {
        if ($this->user_id !== $this->discord->id) {
            if ($botperms = $this->getBotPermissions()) {
                if (! $botperms->manage_channels) {
                    return reject(new NoPermissionsException("You do not have permission to delete channel {$this->id}."));
                }
            }
        }

        return $this->messages->delete($this, $reason);
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
     * @return ?array $overwrites
     */
    protected function getPermissionOverwritesAttribute(): ?array
    {
        $overwrites = [];

        /** @var Overwrite */
        foreach ($this->overwrites as $overwrite) {
            $overwrites[] = $overwrite->getRawAttributes();
        }

        return ! empty($overwrites) ? $overwrites : ($this->attributes['permission_overwrites'] ?? null);
    }

    /**
     * Gets the available tags attribute.
     *
     * @return ExCollectionInterface<Tag>|Tag[] Available forum tags.
     *
     * @since 7.4.0
     */
    protected function getAvailableTagsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('available_tags', Tag::class);
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
        return $this->attributePartHelper('default_reaction_emoji', Reaction::class);
    }

    /**
     * Starts a thread in the channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#start-thread-without-message
     * @link https://discord.com/developers/docs/resources/channel#start-thread-in-forum-channel
     *
     * @param array          $options                          Thread params.
     * @param bool           $options['private']               Whether the thread should be private. Cannot start a private thread in an announcement channel. Ignored in forum channel.
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
     * @return PromiseInterface<Thread>
     *
     * @since 10.0.0 Arguments for `$name`, `$private` and `$auto_archive_duration` are now inside `$options`
     */
    public function startThread(array|string $options, string|bool|null $reason = null, int $_auto_archive_duration = 1440, ?string $_reason = null): PromiseInterface
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

        if ($this->type === self::TYPE_GUILD_FORUM) {
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

            if ($this->type === self::TYPE_GUILD_ANNOUNCEMENT) {
                if ($options['private']) {
                    return reject(new \RuntimeException('You cannot start a private thread within an announcement channel.'));
                }

                $options['type'] = self::TYPE_ANNOUNCEMENT_THREAD;
            } elseif ($this->type === self::TYPE_GUILD_TEXT) {
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
     * Gets the members currently in the voice channel.
     *
     * @return ExCollectionInterface<Member>|Member[] Members in the voice channel.
     */
    public function getMembersAttribute(): ExCollectionInterface
    {
        if ($guild = $this->guild) {
            return $guild->members->filter(fn (Member $member) => $guild->voice_states->filter(fn (VoiceStateUpdate $voice_state) => $voice_state->channel_id === $this->id)->has($member->id));
        }

        return $this->discord->getCollectionClass()::for(Member::class, 'id');
    }

    /**
     * @inheritDoc
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
            // Type was not specified, but we must not assume its default to GUILD_TEXT as that is determined by API
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
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/channel#modify-channel-json-params-guild-channel
     */
    public function getUpdatableAttributes(): array
    {
        if ($this->type === self::TYPE_GROUP_DM) {
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
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return ChannelRepository|PrivateChannelRepository The repository.
     */
    public function getRepository(): ChannelRepository|PrivateChannelRepository
    {
        if (isset($this->attributes['guild_id'])) {
            $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

            return $guild->channels;
        }

        return $this->discord->private_channels;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        $repository = $this->getRepository();

        if (isset($this->attributes['guild_id'])) {
            if ($botperms = $this->getBotPermissions()) {
                if (! $botperms->manage_channels) {
                    return reject(new NoPermissionsException("You do not have permission to manage channels in the guild {$this->attributes['guild_id']}."));
                }
            }
            
            return $repository->save($this, $reason);
        } elseif ($this->created && $repository->get('id', $this->id)) {
            $data = [];
            if ($this->name) {
                $data['name'] = $this->name;
            }
            if ($this->icon) {
                $data['icon'] = $this->icon;
            }

            return $repository->modifyGroupDM($this, $data);
        }

        return parent::save($reason);
    }

    /**
     * @inheritDoc
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

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

use Carbon\Carbon;
use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\ExCollectionInterface;
use Discord\Helpers\Multipart;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Profile;
use Discord\Parts\Guild\Role;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\Channel\InviteRepository;
use React\Promise\PromiseInterface;
use Stringable;

use function React\Promise\reject;

/**
 * An invite to a Channel and Guild.
 *
 * @link https://discord.com/developers/docs/resources/invite
 *
 * @since 7.0.0 Namespace moved from Guild to Channel
 * @since 2.0.0
 *
 * @property int                 $type                       The type of invite
 * @property string              $code                       The invite code.
 * @property Guild|null          $guild                      The partial guild that the invite is for.
 * @property string|null         $guild_id
 * @property ?Channel|null       $channel                    The partial channel that the invite is for.
 * @property string|null         $channel_id
 * @property User|null           $inviter                    The user that created the invite.
 * @property bool|null           $is_nickname_changeable     A member's ability to change their nickname by default, returned from the `GET /invites/<code>` endpoint when `with_permissions` is `true`
 * @property int|null            $target_type                The type of target for this voice channel invite.
 * @property User|null           $target_user                The user whose stream to display for this voice channel stream invite.
 * @property Application|null    $target_application         The partial embedded application to open for this voice channel embedded application invite.
 * @property int|null            $approximate_presence_count Approximate count of online members, returned from the GET /invites/<code> endpoint when with_counts is true.
 * @property int|null            $approximate_member_count   Approximate count of total members, returned from the GET /invites/<code> endpoint when with_counts is true.
 * @property Carbon              $expires_at                 The expiration date of this invite.
 * @property ScheduledEvent|null $guild_scheduled_event      Guild scheduled event data, only included if guild_scheduled_event_id contains a valid guild scheduled event id.
 * @property int                 $flags                      Guild invite flags for guild invites.
 * @property Role[]              $roles                      The roles assigned to the user upon accepting the invite. Contains a limited amount of role information.
 * @property Profile             $profile                    The guild profile.
 *
 * @property int|null    $uses       How many times the invite has been used.
 * @property int|null    $max_uses   How many times the invite can be used.
 * @property int|null    $max_age    How many seconds the invite will be alive.
 * @property bool|null   $temporary  Whether the invite is for temporary membership.
 * @property Carbon|null $created_at A timestamp of when the invite was created.
 *
 * @property-read string $invite_url Returns the invite URL.
 */
class Invite extends Part implements Stringable
{
    public const TYPE_GUILD = 0;
    public const TYPE_GROUP_DM = 1;
    public const TYPE_FRIEND = 2;

    public const TARGET_TYPE_STREAM = 1;
    public const TARGET_TYPE_EMBEDDED_APPLICATION = 2;

    /** This invite is a guest invite for a voice channel. */
    public const FLAG_IS_GUEST_INVITE = 1 << 0;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'code',
        'guild',
        'channel',
        'inviter',
        'is_nickname_changeable',
        'target_type',
        'target_user',
        'target_application',
        'approximate_presence_count',
        'approximate_member_count',
        'expires_at',
        'guild_scheduled_event',
        'flags',
        'profile',

        // Extra metadata
        'uses',
        'max_uses',
        'max_age',
        'temporary',
        'created_at',

        // @internal
        'guild_id',
        'channel_id',
    ];

    /**
     * Returns the id attribute.
     *
     * @return string The id attribute.
     */
    protected function getIdAttribute(): string
    {
        return $this->code;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The Guild that you have been invited to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        if ($guild = $this->discord->guilds->get('id', $this->attributes['guild_id'])) {
            return $guild;
        }

        return $this->attributePartHelper('guild', Guild::class);
    }

    /**
     * Returns the guild id attribute.
     *
     * @return string|null
     */
    protected function getGuildIdAttribute(): ?string
    {
        if (isset($this->attributes['guild_id'])) {
            return $this->attributes['guild_id'];
        }

        if (isset($this->attributes['guild']->id)) {
            return $this->attributes['guild']->id;
        }

        return null;
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|null The Channel that you have been invited to.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($channelId = $this->channel_id) {
            if ($guild = $this->guild) {
                if ($channel = $guild->channels->get('id', $channelId)) {
                    return $channel;
                }
            }

            if ($channel = $this->discord->private_channels->get('id', $channelId)) {
                return $channel;
            }
        }

        if ($channel = $this->attributePartHelper('channel', Channel::class, ['guild_id' => $this->guild_id])) {
            $this->guild->channels->pushItem($channel);
        }

        return $channel;
    }

    /**
     * Returns the channel id attribute.
     *
     * @return ?string The Channel ID that you have been invited to.
     */
    protected function getChannelIdAttribute(): ?string
    {
        if (isset($this->attributes['channel_id'])) {
            return $this->attributes['channel_id'];
        }

        if (isset($this->attributes['channel']->id)) {
            return $this->attributes['channel']->id;
        }

        return null;
    }

    /**
     * Returns the inviter attribute.
     *
     * @return User|null The User that invited you.
     */
    protected function getInviterAttribute(): ?User
    {
        if (! isset($this->attributes['inviter'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['inviter']->id)) {
            return $user;
        }

        return $this->attributePartHelper('inviter', User::class);
    }

    /**
     * Returns the target user attribute.
     *
     * @return User|null The user whose stream to display for this voice channel stream invite.
     */
    protected function getTargetUserAttribute(): ?User
    {
        if (! isset($this->attributes['target_user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['target_user']->id)) {
            return $user;
        }

        return $this->attributePartHelper('target_user', User::class);
    }

    /**
     * Returns the target application attribute.
     *
     * @return Application|null The partial target application data.
     */
    protected function getTargetApplicationAttribute(): ?Application
    {
        return $this->attributePartHelper('target_application', Application::class);
    }

    /**
     * Returns the expires at attribute.
     *
     * @return Carbon|null The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getExpiresAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('expires_at');
    }

    /**
     * Returns the guild scheduled event on this invite.
     *
     * @return ScheduledEvent|null The guild scheduled event data.
     */
    protected function getGuildScheduledEventAttribute(): ?ScheduledEvent
    {
        if (! isset($this->attributes['guild_scheduled_event'])) {
            return null;
        }

        if ($guild = $this->guild) {
            if ($scheduled_event = $guild->guild_scheduled_events->get('id', $this->attributes['guild_scheduled_event']->id)) {
                return $scheduled_event;
            }
        }

        return $this->attributePartHelper('guild_scheduled_event', ScheduledEvent::class);
    }

    /**
     * Returns the roles for this invite.
     *
     * This will only have partial data based on what the invite endpoint provides.
     * Properties for `description`, `hoist`, `managed`, `mentionable`, `tags`, and `flags` will not be set.
     * `permissions` will only contain permissions related to the invite and may not be present at all.
     *
     * @since 10.46.0
     *
     * @return ExCollectionInterface<Role> The roles assigned to the user upon accepting the invite.
     */
    protected function getRolesAttribute(): ExCollectionInterface
    {
        $class = Role::class;

        /** @var ExCollectionInterface $collection */
        $collection = $this->discord->getCollectionClass()::for($class, 'id');

        if (empty($this->attributes['roles'])) {
            return $collection;
        }

        foreach ($this->attributes['roles'] as &$part) {
            if (! $part instanceof $class) {
                $part = $this->createOf($class, $part);
                if ($guild = $this->guild) {
                    if ($role = $guild->roles->get('id', $part->id)) {
                        $part->fill((array) $role);
                    }
                }
            }

            $collection->pushItem($part);
        }

        return $collection;
    }

    /**
     * Gets the users allowed to see and accept this invite.
     *
     * Response is a CSV file with the header `user_id` and each user ID from the original file passed to invite create on its own line.
     *
     * Requires the caller to be the inviter, or have `MANAGE_GUILD` permission, or have `VIEW_AUDIT_LOG` permission.
     *
     * @todo Parse the CSV response to an array.
     * @since 10.46.0
     *
     * @throws NoPermissionsException If the bot does not have permission to view the audit log or manage the guild, and is not the inviter.
     *
     * @return PromiseInterface<array|string> The CSV file's content containing the user IDs.
     */
    public function getTargetUsers(): PromiseInterface
    {
        if ($botperms = $this->channel->getBotPermissions()) {
            if (! $botperms->manage_guild && ! $botperms->view_audit_log && ! $this->inviter->id === $this->discord->user->id) {
                return reject(new NoPermissionsException("You do not have permission to create invites in the channel {$this->channel->id}."));
            }
        }

        return $this->http->get(Endpoint::bind(Endpoint::INVITE_TARGET_USERS, $this->id));
    }

    /**
     * Updates the users allowed to see and accept this invite.
     *
     * Uploading a file with invalid user IDs will result in a 400 with the invalid IDs described.
     *
     * Requires the caller to be the inviter or have the `MANAGE_GUILD` permission.
     *
     * @since 10.46.0
     *
     * @param string      $filepath Path to the file to send.
     * @param string|null $filename Name to send the file as. `null` for the base name of `$filepath`.
     *
     * @throws NoPermissionsException If the bot does not have permission to view the audit log or manage the guild, and is not the inviter.
     * @throws FileNotFoundException  If the file does not exist or is not readable.
     *
     * @return PromiseInterface
     */
    public function updateTargetUsers(string $filepath, ?string $filename = null): PromiseInterface
    {
        if ($botperms = $this->channel->getBotPermissions()) {
            if (! $botperms->manage_guild && ! $this->inviter->id === $this->discord->user->id) {
                return reject(new NoPermissionsException("You do not have permission to create invites in the channel {$this->channel->id}."));
            }
        }

        if (! file_exists($filepath)) {
            return reject(new FileNotFoundException("File does not exist at path {$filepath}."));
        }

        if (($content = file_get_contents($filepath)) === false) {
            return reject(new FileNotFoundException("Unable to read file at path {$filepath}."));
        }

        return $this->updateTargetUsersFromContent($content, $filename ?? basename($filepath));
    }

    /**
     * Updates the users allowed to see and accept this invite.
     *
     * Uploading a file with invalid user IDs will result in a 400 with the invalid IDs described.
     *
     * Requires the caller to be the inviter or have the `MANAGE_GUILD` permission.
     *
     * @since 10.46.0
     *
     * @param string $content  Content of the file.
     * @param string $filename Name to send the file as.
     *
     * @throws NoPermissionsException If the bot does not have permission to view the audit log or manage the guild, and is not the inviter.
     *
     * @return PromiseInterface
     */
    public function updateTargetUsersFromContent(string $content, string $filename = 'target_users.csv'): PromiseInterface
    {
        if ($botperms = $this->channel->getBotPermissions()) {
            if (! $botperms->manage_guild && ! $this->inviter->id === $this->discord->user->id) {
                return reject(new NoPermissionsException("You do not have permission to create invites in the channel {$this->channel->id}."));
            }
        }

        if ($content === '') {
            return reject(new \BadMethodCallException('The provided CSV contents are empty.'));
        }

        $multipart = new Multipart([
            [
                'name' => 'target_users_file',
                'filename' => $filename,
                'content' => $content,
                'headers' => ['Content-Type' => 'text/csv'],
            ],
        ]);

        return $this->http->put(Endpoint::bind(Endpoint::INVITE_TARGET_USERS, $this->id), (string) $multipart, $multipart->getHeaders());
    }
    
    /**
     * Processing target users from a CSV when creating or updating an invite is done asynchronously. This endpoint allows you to check the status of that job.
     *
     * Requires the caller to be the inviter, or have `MANAGE_GUILD` permission, or have `VIEW_AUDIT_LOG` permission.
     *
     * @todo
     *
     * @throws NoPermissionsException If the bot does not have permission to view the audit log or manage the guild, and is not the inviter.
     *
     * @return PromiseInterface<InviteJobStatus> The job status.
     */
    public function getTargetUsersJobStatus()
    {
        if ($botperms = $this->channel->getBotPermissions()) {
            if (! $botperms->manage_guild && ! $botperms->view_audit_log && ! $this->inviter->id === $this->discord->user->id) {
                return reject(new NoPermissionsException("You do not have permission to create invites in the channel {$this->channel->id}."));
            }
        }

        return $this->http->get(Endpoint::bind(Endpoint::INVITE_TARGET_USERS_JOB_STATUS, $this->id))
            ->then(fn ($response) => $this->factory->part(InviteJobStatus::class, (array) $response));
    }

    /**
     * Returns the guild profile for this invite.
     *
     * @return Profile The guild profile.
     */
    protected function getProfileAttribute(): Profile
    {
        return $this->attributePartHelper('profile', Profile::class);
    }

    /**
     * Returns whether the guest invite flag is set.
     *
     * @return bool Whether the invite is a guest invite for a voice channel.
     */
    public function isGuestInvite(): bool
    {
        return ($this->flags & self::FLAG_IS_GUEST_INVITE) === self::FLAG_IS_GUEST_INVITE;
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon|null The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('created_at');
    }

    /**
     * Returns the invite URL attribute.
     *
     * @return string The URL to the invite.
     */
    protected function getInviteUrlAttribute(): string
    {
        return 'https://discord.gg/'.$this->code;
    }

    /**
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return InviteRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): InviteRepository|null
    {
        if (! isset($this->attributes['channel_id'])) {
            return null;
        }

        /** @var Channel $channel */
        $channel = $this->channel ?? $this->factory->part(Channel::class, ['id' => $this->attributes['channel_id']], true);

        return $channel->invites;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (! $this->guild_id) {
            return parent::save();
        }
        
        if (isset($this->attributes['channel_id'])) {
            /** @var Channel $channel */
            $channel = $this->channel ?? $this->factory->part(Channel::class, ['id' => $this->attributes['channel_id']], true);
            if ($botperms = $channel->getBotPermissions()) {
                if (! $botperms->create_instant_invite) {
                    return reject(new NoPermissionsException("You do not have permission to create invites in the channel {$channel->id}."));
                }
            }

            return $channel->invites->save($this, $reason);
        }

        return parent::save();
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'code' => $this->code,
        ];
    }

    public function __toString(): string
    {
        return 'discord.gg/'.$this->code;
    }
}

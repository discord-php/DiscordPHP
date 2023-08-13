<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Helpers\BigInt;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Overwrite;
use Discord\Parts\Guild\Ban;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\Permission;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\Thread\Thread;
use Discord\Parts\WebSockets\PresenceUpdate;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;

/**
 * A member is a relationship between a user and a guild. It contains user-to-guild specific data like roles.
 *
 * @link https://discord.com/developers/docs/resources/guild#guild-member-object
 *
 * @since 2.0.0
 *
 * @property      User|null           $user                         The user part of the member.
 * @property-read string|null         $username                     The username of the member.
 * @property      ?string|null        $nick                         The nickname of the member.
 * @property-read string              $displayname                  The nickname or display name with optional discriminator of the member.
 * @property      ?string|null        $avatar                       The avatar URL of the member or null if member has no guild avatar.
 * @property      ?string|null        $avatar_hash                  The avatar hash of the member or null if member has no guild avatar.
 * @property      Collection|Role[]   $roles                        A collection of Roles that the member has.
 * @property      Carbon|null         $joined_at                    A timestamp of when the member joined the guild.
 * @property      Carbon|null         $premium_since                When the user started boosting the server.
 * @property      bool                $deaf                         Whether the member is deaf.
 * @property      bool                $mute                         Whether the member is mute.
 * @property      bool|null           $pending                      Whether the user has not yet passed the guild's Membership Screening requirements.
 * @property      RolePermission|null $permissions                  Total permissions of the member in the channel, including overwrites, returned when in the interaction object.
 * @property      Carbon|null         $communication_disabled_until When the user's timeout will expire and the user will be able to communicate in the guild again, null or a time in the past if the user is not timed out.
 * @property      int                 $flags                        Guild member flags represented as a bit set, defaults to `0`.
 * @property      string|null         $guild_id                     The unique identifier of the guild that the member belongs to.
 * @property-read Guild|null          $guild                        The guild that the member belongs to.
 *
 * @property      string                $id            The unique identifier of the member.
 * @property      string                $status        The status of the member.
 * @property-read Activity              $game          The game the member is playing.
 * @property      Collection|Activity[] $activities    User's current activities.
 * @property      object                $client_status Current client status.
 *
 * @method ExtendedPromiseInterface<Message> sendMessage(MessageBuilder $builder)
 */
class Member extends Part
{
    public const FLAGS_DID_REJOIN = (1 << 0);
    public const FLAGS_COMPLETED_ONBOARDING = (1 << 1);
    public const FLAGS_BYPASSES_VERIFICATION = (1 << 2);
    public const FLAGS_STARTED_ONBOARDING = (1 << 3);

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'user',
        'nick',
        'avatar',
        'roles',
        'joined_at',
        'premium_since',
        'deaf',
        'mute',
        'pending',
        'permissions',
        'communication_disabled_until',
        'flags',

        // partial
        'guild_id',

        // @internal
        'id',
        'status',
        'activities',
        'client_status',
    ];

    /**
     * Updates the member from a new presence update object.
     * This is an internal function and is not meant to be used by a public application.
     *
     * @internal
     *
     * @param PresenceUpdate $presence
     *
     * @return PresenceUpdate Old presence.
     */
    public function updateFromPresence(PresenceUpdate $presence): PresenceUpdate
    {
        $rawPresence = $presence->getRawAttributes();
        $oldPresence = $this->factory->part(PresenceUpdate::class, (array) $this->attributes, true);

        $this->attributes = array_merge($this->attributes, $rawPresence);

        return $oldPresence;
    }

    /**
     * Bans the member. Alias for `$guild->bans->ban()`.
     *
     * @see BanRepository::ban()
     *
     * @param int|null    $daysToDeleteMessages The amount of days to delete messages from.
     * @param string|null $reason               Reason of the Ban.
     *
     * @throws \RuntimeException      Member has no `$guild`.
     * @throws NoPermissionsException Missing `ban_members` permission.
     *
     * @return ExtendedPromiseInterface<Ban>
     */
    public function ban(?int $daysToDeleteMessages = null, ?string $reason = null): ExtendedPromiseInterface
    {
        return $this->discord->guilds->cacheGet($this->guild_id)->then(function (?Guild $guild) use ($daysToDeleteMessages, $reason) {
            if (null === $guild) {
                return reject(new \RuntimeException('Member has no Guild Part'));
            }

            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->ban_members) {
                    return reject(new NoPermissionsException("You do not have permission to ban members in the guild {$guild->id}."));
                }
            }

            return $guild->bans->ban($this, ['delete_message_days' => $daysToDeleteMessages], $reason);
        });
    }

    /**
     * Alias for `$guild->members->delete()`.
     *
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws \RuntimeException      Member has no `$guild`.
     * @throws NoPermissionsException Missing `kick_members` permission.
     *
     * @return ExtendedPromiseInterface<self>
     */
    public function kick(?string $reason = null): ExtendedPromiseInterface
    {
        return $this->discord->guilds->cacheGet($this->guild_id)->then(function (?Guild $guild) use ($reason) {
            if (null === $guild) {
                return new \RuntimeException('Member has no Guild Part');
            }

            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->kick_members) {
                    return reject(new NoPermissionsException("You do not have permission to kick members in the guild {$guild->id}."));
                }
            }

            return $guild->members->delete($this, $reason);
        });
    }

    /**
     * Sets the nickname of the member.
     *
     * @param ?string|null $nick   The nickname of the member.
     * @param string|null  $reason Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_nicknames permission.
     *
     * @return ExtendedPromiseInterface<Member>
     */
    public function setNickname(?string $nick = null, ?string $reason = null): ExtendedPromiseInterface
    {
        $payload = [
            'nick' => $nick ?? '',
        ];

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        // jake plz
        if ($this->discord->id == $this->id) {
            return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER_SELF, $this->guild_id), $payload, $headers);
        }

        if ($guild = $this->guild) {
            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->manage_nicknames) {
                    return reject(new NoPermissionsException("You do not have permission to manage nicknames in the guild {$guild->id}."));
                }
            }
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), $payload, $headers)
            ->then(function ($response) {
                $this->nick = $response->nick;

                return $this;
            });
    }

    /**
     * Moves the member to another voice channel.
     *
     * @param Channel|?string $channel The channel to move the member to.
     * @param string|null     $reason  Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Member>
     */
    public function moveMember($channel, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($channel instanceof Channel) {
            $channel = $channel->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), ['channel_id' => $channel], $headers)
            ->then(function ($response) {
                return $this;
            });
    }

    /**
     * Adds a role to the member.
     *
     * @link https://discord.com/developers/docs/resources/guild#add-guild-member-role
     *
     * @param Role|string $role   The role to add to the member.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing manage_roles permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function addRole($role, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($role instanceof Role) {
            $role = $role->id;
        }

        // We don't want a double up on roles
        if (in_array($role, (array) $this->attributes['roles'])) {
            return reject(new \RuntimeException('Member already has role.'));
        }

        if ($guild = $this->guild) {
            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->manage_roles) {
                    return reject(new NoPermissionsException("You do not have permission to add member role in the guild {$guild->id}."));
                }
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(Endpoint::bind(Endpoint::GUILD_MEMBER_ROLE, $this->guild_id, $this->id, $role), null, $headers)
            ->then(function () use ($role) {
                if (! in_array($role, $this->attributes['roles'])) {
                    $this->attributes['roles'][] = $role;
                }
            });
    }

    /**
     * Removes a role from the member.
     *
     * @link https://discord.com/developers/docs/resources/guild#remove-guild-member-role
     *
     * @param Role|string $role   The role to remove from the member.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_roles permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function removeRole($role, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($role instanceof Role) {
            $role = $role->id;
        }

        if ($guild = $this->guild) {
            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->manage_roles) {
                    return reject(new NoPermissionsException("You do not have permission to remove member role in the guild {$guild->id}."));
                }
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::GUILD_MEMBER_ROLE, $this->guild_id, $this->id, $role), null, $headers)
            ->then(function () use ($role) {
                if ($removeRole = array_search($role, $this->attributes['roles']) !== false) {
                    unset($this->attributes['roles'][$removeRole]);
                }
            });
    }

    /**
     * Updates member roles.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-member
     *
     * @param Role[]|string[] $roles  The roles to set to the member.
     * @param string|null     $reason Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_roles permission.
     *
     * @return ExtendedPromiseInterface<Member>
     */
    public function setRoles(array $roles, ?string $reason = null): ExtendedPromiseInterface
    {
        foreach ($roles as $i => $role) {
            if ($role instanceof Role) {
                $roles[$i] = $role->id;
            }
        }

        if ($guild = $this->guild) {
            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->manage_roles) {
                    return reject(new NoPermissionsException("You do not have permission to manage member roles in the guild {$guild->id}."));
                }
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), ['roles' => $roles], $headers)
            ->then(function ($response) {
                $this->attributes['roles'] = $response->roles;

                return $this;
            });
    }

    /**
     * Sends a message to the member.
     *
     * Takes a `MessageBuilder` or content of the message for the first parameter. If the first parameter
     * is an instance of `MessageBuilder`, the rest of the arguments are disregarded.
     *
     * @see User::sendMessage()
     *
     * @param MessageBuilder|string $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                  $tts              Whether the message is TTS.
     * @param Embed|array|null      $embed            An embed object or array to send in the message.
     * @param array|null            $allowed_mentions Allowed mentions object for the message.
     * @param Message|null          $replyTo          Sends the message as a reply to the given message instance.
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendMessage($message, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): ExtendedPromiseInterface
    {
        if ($user = $this->user) {
            return $user->sendMessage($message, $tts, $embed, $allowed_mentions, $replyTo);
        }

        return reject(new \RuntimeException('Member had no user part.'));
    }

    /**
     * Gets the total permissions of the member.
     *
     * Note that Discord permissions are complex and YOU need to account for the
     * fact that you cannot edit a role higher than your own.
     *
     * @link https://discord.com/developers/docs/topics/permissions
     *
     * @param Channel|Thread|null $channel The channel to check its permission overwrites. `null` for just Role.
     *
     * @throws \InvalidArgumentException
     *
     * @return RolePermission|null `null` if permission is failed to be determined.
     */
    public function getPermissions($channel = null): ?RolePermission
    {
        if (! $guild = $this->guild) {
            return null;
        }

        if ($channel) {
            if ($channel instanceof Thread) {
                $channel = $channel->parent;
            } elseif (! ($channel instanceof Channel)) {
                throw new \InvalidArgumentException('$channel must be an instance of Channel, Thread or null.');
            }
        }

        // Get @everyone role guild permission
        if (! $everyoneRole = $guild->roles->get('id', $guild->id)) {
            return null;
        }
        $bitwise = $everyoneRole->permissions->bitwise;

        // If this member is the guild owner
        if ($guild->owner_id == $this->id) {
            // Add administrator permission
            $bitwise = BigInt::set($bitwise, Permission::ROLE_PERMISSIONS['administrator']);
        } else {
            // Prepare array for role ids
            $roles = [];

            // Iterate all base roles
            /** @var Role */
            foreach ($this->roles as $id => $role) {
                // Remember the role id for later use
                $roles[] = $id;
                // Store permission value from this role
                $bitwise = BigInt::or($bitwise, $role->permissions->bitwise);
            }
        }

        // Create from computed base permissions
        /** @var RolePermission */
        $newPermission = $guild->createOf(RolePermission::class, ['bitwise' => $bitwise]);

        // If computed roles has Administrator permission
        if ($newPermission->administrator) {
            // Iterate all permissions of the computed roles
            foreach (RolePermission::getPermissions() as $permission => $_) {
                // Set permission to true
                $newPermission->{$permission} = true;
            }

            // Administrators ends here with all permissions
            return $newPermission;
        }

        // If channel is specified (overwrites)
        if ($channel) {
            // Get @everyone role channel permission
            /** @var Overwrite */
            if ($overwrite = $channel->overwrites->get('id', $guild->id)) {
                // Set "DENY" overwrites
                $bitwise = BigInt::and($bitwise, BigInt::not($overwrite->deny->bitwise));
                // Set "ALLOW" overwrites
                $bitwise = BigInt::or($bitwise, $overwrite->allow->bitwise);
            }

            // Prepare Allow and Deny buffers for role overwrite
            $allow = $deny = 0;

            // Iterate all roles channel permission
            /** @var Overwrite */
            foreach ($channel->overwrites as $overwrite) {
                // Check for Role overwrite or invalid roles
                if ($overwrite->type !== Overwrite::TYPE_ROLE || ! in_array($overwrite->id, $roles)) {
                    // Skip
                    continue;
                }

                // Get "ALLOW" permissions
                $allow = BigInt::or($allow, $overwrite->allow->bitwise);
                // Get "DENY" permissions
                $deny = BigInt::or($deny, $overwrite->deny->bitwise);
            }

            // Set role "DENY" permissions overwrite
            $bitwise = BigInt::and($bitwise, BigInt::not($deny));
            // Set role "ALLOW" permissions overwrite
            $bitwise = BigInt::or($bitwise, $allow);

            // Get this member specific overwrite
            /** @var Overwrite */
            if ($overwrite = $channel->overwrites->get('id', $this->id)) {
                // Set member "DENY" permissions overwrite
                $bitwise = BigInt::and($bitwise, BigInt::not($overwrite->deny->bitwise));
                // Set member "ALLOW" permissions overwrite
                $bitwise = BigInt::or($bitwise, $overwrite->allow->bitwise);
            }
        }

        // Re-create the Role Permissions from the computed overwrites
        return $guild->createOf(RolePermission::class, ['bitwise' => $bitwise]);
    }

    /**
     * Sets timeout on a member.
     *
     * @param Carbon|null $communication_disabled_until When the user's timeout will expire and the user will be able to communicate in the guild again, null or a time in the past if the user is not timed out.
     * @param string|null $reason                       Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing moderate_members permission.
     *
     * @return ExtendedPromiseInterface<Member>
     */
    public function timeoutMember(?Carbon $communication_disabled_until, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($guild = $this->guild) {
            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->moderate_members) {
                    return reject(new NoPermissionsException("You do not have permission to time out members in the guild {$guild->id}."));
                }
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), ['communication_disabled_until' => isset($communication_disabled_until) ? $communication_disabled_until->toIso8601ZuluString() : null], $headers)
            ->then(function ($response) {
                $this->attributes['communication_disabled_until'] = $response->communication_disabled_until;

                return $this;
            });
    }

    /**
     * Sets verification bypasses flag on a member.
     *
     * @param bool        $bypasses_verification Whether member is exempt from guild verification requirements.
     * @param string|null $reason                Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing `moderate_members` permission.
     *
     * @return ExtendedPromiseInterface<Member>
     */
    public function setBypassesVerification(bool $bypasses_verification, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($guild = $this->guild) {
            if ($botperms = $guild->getBotPermissions()) {
                if (! $botperms->moderate_members) {
                    return reject(new NoPermissionsException("You do not have permission to modify member flag in the guild {$guild->id}."));
                }
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        $flags = $this->flags;
        if ($bypasses_verification) {
            $flags |= self::FLAGS_BYPASSES_VERIFICATION;
        } else {
            $flags &= ~self::FLAGS_BYPASSES_VERIFICATION;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), ['flags' => $flags], $headers)
            ->then(function ($response) {
                $this->attributes['flags'] = $response->flags;

                return $this;
            });
    }

    /**
     * Returns the member nickname or display name with optional #discriminator.
     *
     * @return string Either nick or global_name or username with optional #discriminator.
     */
    protected function getDisplaynameAttribute(): string
    {
        $user = $this->user;

        return ($this->nick ?? $user->global_name ?? $user->username).($user->discriminator ? '#'.$user->discriminator : '');
    }

    /**
     * Gets the game attribute.
     * Polyfill for the first activity.
     *
     * @return Activity
     */
    protected function getGameAttribute(): ?Activity
    {
        return $this->activities->get('type', Activity::TYPE_GAME);
    }

    /**
     * Gets the activities attribute.
     *
     * @return Collection|Activity[]
     */
    protected function getActivitiesAttribute(): Collection
    {
        $activities = Collection::for(Activity::class, null);

        foreach ($this->attributes['activities'] ?? [] as $activity) {
            $activities->pushItem($this->createOf(Activity::class, $activity));
        }

        return $activities;
    }

    /**
     * Returns the id attribute.
     *
     * @return string The user ID of the member.
     */
    protected function getIdAttribute(): string
    {
        return $this->attributes['id'] ?? $this->attributes['user']->id;
    }

    /**
     * Returns the username attribute.
     *
     * @return string|null The username of the member.
     */
    protected function getUsernameAttribute(): ?string
    {
        return $this->user->username ?? null;
    }

    /**
     * Returns the discriminator attribute.
     *
     * @deprecated 10.0.0 Use `$member->user->discriminator`
     *
     * @return string|null The discriminator of the member.
     */
    protected function getDiscriminatorAttribute(): ?string
    {
        return $this->user->discriminator ?? null;
    }

    /**
     * Returns the user attribute.
     *
     * @return User|null The user that owns the member.
     */
    protected function getUserAttribute(): ?User
    {
        if ($user = $this->discord->users->get('id', $this->id)) {
            return $user;
        }

        if (! isset($this->attributes['user'])) {
            return null;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the roles attribute.
     *
     * @return Collection<?Role> A collection of roles the member is in. null role only contains ID in the collection.
     */
    protected function getRolesAttribute(): Collection
    {
        $roles = new Collection();

        if (empty($this->attributes['roles'])) {
            return $roles;
        }

        $roles->fill(array_fill_keys($this->attributes['roles'], null));

        if ($guild = $this->guild) {
            foreach ($guild->roles as $id => $role) {
                if (in_array($id, $this->attributes['roles'])) {
                    $roles->pushItem($role);
                }
            }
        }

        return $roles;
    }

    /**
     * Returns the joined at attribute.
     *
     * @return Carbon|null The timestamp from when the member joined.
     *
     * @throws \Exception
     */
    protected function getJoinedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['joined_at'])) {
            return null;
        }

        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * Returns the guild avatar URL for the member.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
     *
     * @return string|null The URL to the member avatar or null.
     */
    public function getAvatarAttribute(?string $format = null, int $size = 1024): ?string
    {
        if (! isset($this->attributes['avatar'])) {
            return null;
        }

        if (isset($format)) {
            $allowed = ['png', 'jpg', 'webp', 'gif'];

            if (! in_array(strtolower($format), $allowed)) {
                $format = 'webp';
            }
        } elseif (strpos($this->attributes['avatar'], 'a_') === 0) {
            $format = 'gif';
        } else {
            $format = 'webp';
        }

        return "https://cdn.discordapp.com/guilds/{$this->guild_id}/users/{$this->id}/avatars/{$this->attributes['avatar']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild avatar hash for the member.
     *
     * @return ?string|null The member avatar's hash or null.
     */
    protected function getAvatarHashAttribute(): ?string
    {
        return $this->attributes['avatar'] ?? null;
    }

    /**
     * Returns the premium since attribute.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getPremiumSinceAttribute(): ?Carbon
    {
        if (! isset($this->attributes['premium_since'])) {
            return null;
        }

        return Carbon::parse($this->attributes['premium_since']);
    }

    /**
     * Returns the permissions attribute.
     * This is only available from Interaction, use Member::getPermissions() for normal permissions.
     *
     * @see Member::getPermissions()
     *
     * @return RolePermission|null The total calculated permissions, only available from Interaction.
     */
    protected function getPermissionsAttribute(): ?RolePermission
    {
        if (! isset($this->attributes['permissions'])) {
            return null;
        }

        return $this->factory->part(RolePermission::class, ['bitwise' => $this->attributes['permissions']], true);
    }

    /**
     * Returns the communication disabled until attribute.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getCommunicationDisabledUntilAttribute(): ?Carbon
    {
        if (! isset($this->attributes['communication_disabled_until'])) {
            return null;
        }

        return Carbon::parse($this->attributes['communication_disabled_until']);
    }

    /**
     * Returns the voicechannel of the member.
     *
     * @return Channel|null
     */
    public function getVoiceChannel(): ?Channel
    {
        if ($guild = $this->guild) {
            return $guild->channels->find(function (Channel $channel) {
                if ($channel->isVoiceBased() && $members = $channel->members) {
                    return $members->offsetExists($this->id);
                }

                return false;
            });
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-member-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'roles' => array_values($this->attributes['roles']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'user_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "<@{$this->id}>";
    }
}

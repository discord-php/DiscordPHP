<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Overwrite;
use Discord\Parts\Guild\Ban;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\Helpers\Deferred;
use React\Promise\ExtendedPromiseInterface;
use function React\Partial\bind as Bind;

/**
 * A member is a relationship between a user and a guild. It contains user-to-guild specific data like roles.
 *
 * @property string                       $id            The unique identifier of the member.
 * @property string                       $username      The username of the member.
 * @property string                       $discriminator The discriminator of the member.
 * @property User                         $user          The user part of the member.
 * @property Collection|Role[]            $roles         A collection of Roles that the member has.
 * @property bool                         $deaf          Whether the member is deaf.
 * @property bool                         $mute          Whether the member is mute.
 * @property Carbon|null                  $joined_at     A timestamp of when the member joined the guild.
 * @property Guild                        $guild         The guild that the member belongs to.
 * @property string                       $guild_id      The unique identifier of the guild that the member belongs to.
 * @property string                       $status        The status of the member.
 * @property Activity                     $game          The game the member is playing.
 * @property string|null                  $nick          The nickname of the member.
 * @property Carbon|null                  $premium_since When the user started boosting the server.
 * @property Collection|Activity[]        $activities User's current activities.
 * @property object                       $client_status Current client status
 */
class Member extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'roles', 'deaf', 'mute', 'joined_at', 'guild_id', 'status', 'game', 'nick', 'premium_since', 'activities', 'client_status'];

    /**
     * {@inheritdoc}
     */
    protected $fillAfterSave = false;

    /**
     * Updates the member from a new presence update object.
     * This is an internal function and is not meant to be used by a public application.
     *
     * @param PresenceUpdate $presence
     *
     * @return PresenceUpdate Old presence.
     * @throws \Exception
     */
    public function updateFromPresence(PresenceUpdate $presence): Part
    {
        $rawPresence = $presence->getRawAttributes();
        $oldPresence = $this->factory->create(PresenceUpdate::class, $this->attributes, true);

        $this->attributes = array_merge($this->attributes, $rawPresence);

        return $oldPresence;
    }

    /**
     * Bans the member. Alias for `$guild->bans->ban()`.
     *
     * @param int|null    $daysToDeleteMessages The amount of days to delete messages from.
     * @param string|null $reason
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function ban(?int $daysToDeleteMessages = null, ?string $reason = null): ExtendedPromiseInterface
    {
        return $this->guild->bans->ban($this, $daysToDeleteMessages, $reason);
    }

    /**
     * Sets the nickname of the member.
     *
     * @param string|null $nick The nickname of the member.
     *
     * @return ExtendedPromiseInterface
     */
    public function setNickname(?string $nick = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $nick = $nick ?: '';
        $payload = [
            'nick' => $nick,
        ];

        // jake plz
        if ($this->discord->id == $this->id) {
            $promise = $this->http->patch("guilds/{$this->guild_id}/members/@me/nick", $payload);
        } else {
            $promise = $this->http->patch("guilds/{$this->guild_id}/members/{$this->id}", $payload);
        }

        $promise->done(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Moves the member to another voice channel.
     *
     * @param Channel|string $channel The channel to move the member to.
     *
     * @return ExtendedPromiseInterface
     */
    public function moveMember($channel): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if ($channel instanceof Channel) {
            $channel = $channel->id;
        }

        $this->http->patch(
            "guilds/{$this->guild_id}/members/{$this->id}",
            [
                'channel_id' => $channel,
            ]
        )->done(function () use ($deferred) {
            $deferred->resolve();
        }, Bind([$deferred, 'reject']));

        // At the moment we are unable to check if the member
        // was moved successfully.

        return $deferred->promise();
    }

    /**
     * Adds a role to the member.
     *
     * @param Role|string $role The role to add to the member.
     *
     * @return ExtendedPromiseInterface
     */
    public function addRole($role): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if ($role instanceof Role) {
            $role = $role->id;
        }

        // We don't want a double up on roles
        if (false !== array_search($role, (array) $this->attributes['roles'])) {
            $deferred->reject(new \Exception('User already has role.'));
        } else {
            $this->http->put(
                "guilds/{$this->guild_id}/members/{$this->id}/roles/{$role}"
            )->done(function () use ($role, $deferred) {
                $this->attributes['roles'][] = $role;
                $deferred->resolve();
            }, Bind([$deferred, 'reject']));
        }

        return $deferred->promise();
    }

    /**
     * Removes a role from the user.
     *
     * @param Role|string $role The role to remove from the member.
     *
     * @return ExtendedPromiseInterface
     */
    public function removeRole($role): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if ($role instanceof Role) {
            $role = $role->id;
        }

        if (false !== ($index = array_search($role, $this->attributes['roles']))) {
            $this->http->delete(
                "guilds/{$this->guild_id}/members/{$this->id}/roles/{$role}"
            )->done(function () use ($index, $deferred) {
                unset($this->attributes['roles'][$index]);
                $deferred->resolve();
            }, Bind([$deferred, 'reject']));
        } else {
            $deferred->reject(new \Exception('User does not have role.'));
        }

        return $deferred->promise();
    }

    /**
     * Gets the total permissions of the member.
     *
     * Note that Discord permissions are complex and YOU
     * need to account for the fact that you cannot edit
     * a role higher than your own.
     *
     * @see https://discord.com/developers/docs/topics/permissions
     *
     * @param Channel|null $channel
     *
     * @return RolePermission
     */
    public function getPermissions(?Channel $channel = null): RolePermission
    {
        $bitwise = $this->guild->roles->get('id', $this->guild_id)->permissions->bitwise;

        if ($this->guild->owner_id == $this->id) {
            $bitwise |= 0x8; // Add administrator permission
        } else {
            $roles = [];
            
            /* @var Role */
            foreach ($this->roles ?? [] as $role) {
                $roles[] = $role->id;
                $bitwise |= $role->permissions->bitwise;
            }
        }

        /** @var RolePermission */
        $newPermission = $this->factory->part(RolePermission::class, ['bitwise' => $bitwise]);

        if ($newPermission->administrator) {
            foreach (RolePermission::getPermissions() as $permission => $_) {
                $newPermission->{$permission} = true;
            }
            
            return $newPermission;
        }

        if ($channel) {
            /* @var Overwrite */
            if ($overwrite = $channel->overwrites->get('id', $this->guild->id)) {
                $bitwise |= $overwrite->allow->bitwise;
                $bitwise &= ~($overwrite->deny->bitwise);
            }
            
            /* @var Overwrite */
            foreach ($channel->overwrites as $overwrite) {
                if ($overwrite->type !== 'role' || ! in_array($overwrite->id, $roles)) {
                    continue;
                }

                $bitwise |= $overwrite->allow->bitwise;
                $bitwise &= ~($overwrite->deny->bitwise);
            }

            /* @var Overwrite */
            if ($overwrite = $channel->overwrites->get('id', $this->id)) {
                $bitwise |= $overwrite->allow->bitwise;
                $bitwise &= ~($overwrite->deny->bitwise);
            }
        }

        /** @var RolePermission */
        $newPermission = $this->factory->part(RolePermission::class, ['bitwise' => $bitwise]);

        return $newPermission;
    }

    /**
     * Gets the game attribute.
     *
     * @return Activity
     * @throws \Exception
     */
    protected function getGameAttribute(): Part
    {
        return $this->factory->create(Activity::class, $this->attributes['game'] ?? [], true);
    }

    /**
     * Gets the activities attribute.
     *
     * @return Collection|Activity[]
     * @throws \Exception
     */
    protected function getActivitiesAttribute(): Collection
    {
        $activities = new Collection([], null);

        foreach ($this->attributes['activities'] ?? [] as $activity) {
            $activities->push($this->factory->create(Activity::class, $activity, true));
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
        return $this->attributes['user']->id;
    }

    /**
     * Returns the username attribute.
     *
     * @return string The username of the member.
     */
    protected function getUsernameAttribute(): string
    {
        return $this->user->username;
    }

    /**
     * Returns the discriminator attribute.
     *
     * @return string The discriminator of the member.
     */
    protected function getDiscriminatorAttribute(): string
    {
        return $this->user->discriminator;
    }

    /**
     * Returns the user attribute.
     *
     * @return User       The user that owns the member.
     * @throws \Exception
     */
    protected function getUserAttribute(): User
    {
        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->create(User::class, $this->attributes['user'], true);
    }

    /**
     * Returns the guild attribute.
     *
     * @return null|Guild The guild.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the roles attribute.
     *
     * @return Collection A collection of roles the member is in.
     * @throws \Exception
     */
    protected function getRolesAttribute(): Collection
    {
        $roles = new Collection();

        if ($guild = $this->guild) {
            foreach ($guild->roles as $role) {
                if (array_search($role->id, $this->attributes['roles'] ?? []) !== false) {
                    $roles->push($role);
                }
            }
        } else {
            foreach ($this->attributes['roles'] ?? [] as $role) {
                $roles->push($this->factory->create(Role::class, $role, true));
            }
        }

        return $roles;
    }

    /**
     * Returns the joined at attribute.
     *
     * @return Carbon|null     The timestamp from when the member joined.
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
     * Returns the premium since attribute.
     *
     * @return Carbon|null
     */
    protected function getPremiumSinceAttribute(): ?Carbon
    {
        if (! isset($this->attributes['premium_since'])) {
            return null;
        }

        return Carbon::parse($this->attributes['premium_since']);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'roles' => array_values($this->attributes['roles']),
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->nick) {
            return "<@!{$this->id}>";
        }

        return "<@{$this->id}>";
    }
}

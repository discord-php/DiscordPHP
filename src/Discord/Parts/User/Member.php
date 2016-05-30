<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Cache\Cache;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Ban;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission as Permission;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;

/**
 * A member is a relationship between a user and a guild. It contains user-to-guild specific data like roles.
 *
 * @property string       $id
 * @property string       $username
 * @property string       $discriminator
 * @property User         $user
 * @property array|Role[] $roles
 * @property bool         $deaf
 * @property bool         $mute
 * @property Carbon       $joined_at
 * @property string       $guild_id
 * @property string       $status
 * @property string       $game
 */
class Member extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'roles', 'deaf', 'mute', 'joined_at', 'guild_id', 'status', 'game'];

    /**
     * {@inheritdoc}
     */
    protected $fillAfterSave = false;

    /**
     * Bans the member.
     *
     * @param int $daysToDeleteMessasges The amount of days to delete messages from.
     *
     * @return \React\Promise\Promise
     */
    public function ban($daysToDeleteMessasges = null)
    {
        $deferred = new Deferred();

        $url = $this->replaceWithVariables('guilds/:guild_id/bans/:id');

        if (! is_null($daysToDeleteMessasges)) {
            $url .= "?message-delete-days={$daysToDeleteMessasges}";
        }

        $this->http->put($url)->then(
            function () use ($deferred) {
                $deferred->resolve($this->factory->create(Ban::class,
                    [
                        'user'  => $this->user,
                        'guild' => new Guild(['id' => $this->guild_id], true),
                    ], true
                ));
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Moves the member to another voice channel.
     *
     * @param Channel|int $channel The channel to move the member to.
     *
     * @return \React\Promise\Promise
     */
    public function moveMember($channel)
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
        )->then(function () use ($deferred) {
            $deferred->resolve();
        }, \React\Partial\bind_right($this->reject, $deferred));

        // At the moment we are unable to check if the member
        // was moved successfully.

        return $deferred->promise();
    }

    /**
     * Adds a role to the member.
     *
     * @param Role|int $role The role to add to the member.
     *
     * @return \React\Promise\Promise
     */
    public function addRole($role)
    {
        if (is_int($role)) {
            $role = new Role(['id' => $role], true);
        }

        // We don't want a double up on roles
        if (false !== array_search($role->id, (array) $this->attributes['roles'])) {
            return false;
        }

        $this->attributes['roles'][] = $role->id;
        $this->roles->push($role);

        return \React\Promise\resolve();
    }

    /**
     * Removes a role from the user.
     *
     * @param Role|int $role The role to remove from the member.
     *
     * @return \React\Promise\Promise
     */
    public function removeRole($role)
    {
        if ($role instanceof Role) {
            $role = $role->id;
        }

        if (false !== $index = array_search($role, $this->attributes['roles'])) {
            unset($this->attributes['roles'][$index]);
        }

        $rolePart = $this->roles->get('id', $role);

        if (false !== $index = array_search($rolePart, $this->roles->all())) {
            $this->roles->pull($index);
        }

        return \React\Promise\resolve();
    }

    /**
     * Gets the game attribute.
     *
     * @return Game The game attribute.
     */
    public function getGameAttribute()
    {
        return new Game((array) $this->attributes['game'], true);
    }

    /**
     * Returns the id attribute.
     *
     * @return int The user ID of the member.
     */
    public function getIdAttribute()
    {
        return $this->user->id;
    }

    /**
     * Returns the username attribute.
     *
     * @return string The username of the member.
     */
    public function getUsernameAttribute()
    {
        return $this->user->username;
    }

    /**
     * Returns the discriminator attribute.
     *
     * @return string The discriminator of the member.
     */
    public function getDiscriminatorAttribute()
    {
        return $this->user->discriminator;
    }

    /**
     * Returns the user attribute.
     *
     * @return User The user that owns the member.
     */
    public function getUserAttribute()
    {
        return $this->factory->create(User::class, $this->attributes['user'], true);
    }

    /**
     * Returns the roles attribute.
     *
     * @return Collection A collection of roles the member is in.
     */
    public function getRolesAttribute()
    {
        $roles = new Collection();

        $guildRoles = $this->cache->get("guild.{$this->guild_id}.roles");

        foreach ($guildRoles as $role) {
            if (array_search($role->id, $this->attributes['roles']) !== false) {
                $roles->push($role);
            }
        }

        return \React\Promise\resolve($roles);
    }

    /**
     * Returns the joined at attribute.
     *
     * @return Carbon The timestamp from when the member joined.
     */
    public function getJoinedAtAttribute()
    {
        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'roles' => $this->attributes['roles'],
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string
     */
    public function __toString()
    {
        return "<@{$this->user->id}>";
    }
}

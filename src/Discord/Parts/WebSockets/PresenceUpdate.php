<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Game;
use Discord\Parts\User\User;
use Illuminate\Support\Collection;

/**
 * A PresenceUpdate part is used when the `PRESENCE_UPDATE` event is fired on the WebSocket. It contains
 * information about the users presence suck as their status (online/away) and their current game.
 *
 * @property \Discord\Parts\User\User   $user The user that the presence update affects.
 * @property Collection[Role]           $roles The roles that the user has.
 * @property \Discord\Parts\Guild\Guild $guild The guild that the presence update affects.
 * @property string                     $guild_id The unique identifier of the guild that the presence update affects.
 * @property string                     $status The updated status of the user.
 * @property \Discord\Parts\User\Game   $game The updated game of the user.
 */
class PresenceUpdate extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'roles', 'guild_id', 'status', 'game', 'nick'];

    /**
     * Gets the user attribute.
     *
     * @return User The user that had their presence updated.
     */
    public function getUserAttribute()
    {
        if ($this->discord->users->has($this->attributes['user']->id)) {
            return $this->discord->users->get($this->attributes['user']->id);
        }
        
        return $this->factory->create(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild The guild that the user was in.
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the game attribute.
     *
     * @return Game The game attribute.
     */
    public function getGameAttribute()
    {
        return $this->factory->create(Game::class, (array) $this->attributes['game'], true);
    }
}

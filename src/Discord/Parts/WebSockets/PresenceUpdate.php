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
use Discord\Parts\User\User;

/**
 * A PresenceUpdate part is used when the `PRESENCE_UPDATE` event is fired on the WebSocket. It contains
 * information about the users presence suck as their status (online/away) and their current game.
 *
 * @property User         $user
 * @property array|Role[] $roles
 * @property string       $guild_id
 * @property string       $status
 * @property string       $game
 */
class PresenceUpdate extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'roles', 'guild_id', 'status', 'game'];

    /**
     * Gets the user attribute.
     *
     * @return User The user that had their presence updated.
     */
    public function getUserAttribute()
    {
        return new User((array) $this->attributes['user'], true);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild The guild that the user was in.
     */
    public function getGuildAttribute()
    {
        return new Guild(
            [
                'id' => $this->guild_id,
            ], true
        );
    }
}

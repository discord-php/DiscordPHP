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
use Discord\Parts\Part;
use Discord\Parts\User\User;

class PresenceUpdate extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array
     */
    protected $fillable = ['user', 'roles', 'guild_id', 'status', 'game'];

    /**
     * Gets the user attribute.
     *
     * @return User
     */
    public function getUserAttribute()
    {
        return new User((array) $this->attributes['user'], true);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild
     */
    public function getGuildAttribute()
    {
        return new Guild([
            'id' => $this->guild_id,
        ], true);
    }
}

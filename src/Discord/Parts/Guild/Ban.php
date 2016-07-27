<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * A Ban is a ban on a user specific to a guild. It is also IP based.
 *
 * @property \Discord\Parts\User\User   $user   The user that was banned.
 * @property \Discord\Parts\Guild\Guild $guild  The guild that the user was banned from.
 * @property string|null                $reason The reason the user was banned.
 */
class Ban extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'guild', 'reason'];

    /**
     * Returns the guild id attribute.
     *
     * @return int The Guild ID attribute.
     */
    public function getGuildIdAttribute()
    {
        return $this->guild->id;
    }

    /**
     * Returns the user id attribute.
     *
     * @return int The User ID attribute.
     */
    public function getUserIdAttribute()
    {
        return $this->user->id;
    }

    /**
     * Gets the user attribute.
     *
     * @return User The User that is banned.
     */
    public function getUserAttribute()
    {
        return $this->factory->create(User::class, (array) $this->attributes['user']);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild The guild that the user is banned from.
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->attributes['guild']->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [];
    }
}

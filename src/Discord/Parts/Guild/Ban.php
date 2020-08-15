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
 * @property string $guild_id
 * @property \Discord\Parts\User\User $user
 */
class Ban extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'guild_id'];

    /**
     * Returns the user id of the ban.
     * 
     * @return string
     */
    public function getUserIdAttribute()
    {
        if (isset($this->attributes['user']->id)) return $this->attributes['user']->id;
    }

    /** 
     * Returns the guild attribute of the ban.
     * 
     * @return \Discord\Parts\Guild\Guild
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the user attribute of the ban.
     * 
     * @return \Discord\Parts\User\User
     */
    public function getUserAttribute()
    {
        if (isset($this->attributes['user']->id) && $user = $this->discord->users->get('id', $this->attributes['user']->id)) return $user;
        if (isset($this->attributes['user']) && $user = $this->factory->create(User::class, $this->attributes['user'], true)) return $user;
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

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

class Ban extends Part
{
    /**
     * Is the part editable?
     *
     * @var bool
     */
    public $editable = false;

    /**
     * Is the part findable?
     *
     * @var bool
     */
    public $findable = false;

    /**
     * The parts fillable attributes.
     *
     * @var array
     */
    protected $fillable = ['user', 'guild'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array
     */
    protected $uris = [
        'create' => 'guilds/:guild_id/bans/:user_id',
        'delete' => 'guilds/:guild_id/bans/:user_id',
    ];

    /**
     * Returns the guild id attribute.
     *
     * @return int
     */
    public function getGuildIdAttribute()
    {
        return $this->guild->id;
    }

    /**
     * Returns the user id attribute.
     *
     * @return int
     */
    public function getUserIdAttribute()
    {
        return $this->user->id;
    }

    /**
     * Gets the user attribute.
     *
     * @return User
     */
    public function getUserAttribute()
    {
        return new User((array) $this->attributes['user']);
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array
     */
    public function getCreatableAttributes()
    {
        return [];
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array
     */
    public function getUpdatableAttributes()
    {
        return [];
    }
}

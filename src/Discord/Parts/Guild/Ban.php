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
 * @property User  $user
 * @property Guild $guild
 */
class Ban extends Part
{
    /**
     * {@inheritdoc}
     */
    public $editable = false;

    /**
     * {@inheritdoc}
     */
    public $findable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'guild'];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'create' => 'guilds/:guild_id/bans/:user_id',
        'delete' => 'guilds/:guild_id/bans/:user_id',
    ];

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
        return new User((array) $this->attributes['user']);
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

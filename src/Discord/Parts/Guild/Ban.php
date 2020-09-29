<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
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
 * @property User $user
 * @property string $reason
 */
class Ban extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user', 'guild_id', 'reason'];

    /**
     * Returns the user id of the ban.
     *
     * @return string|null
     */
    protected function getUserIdAttribute(): ?string
    {
        if (isset($this->attributes['user']->id)) {
            return $this->attributes['user']->id;
        }

        return null;
    }

    /**
     * Returns the guild attribute of the ban.
     *
     * @return Guild
     */
    protected function getGuildAttribute(): Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the user attribute of the ban.
     *
     * @return User
     */
    protected function getUserAttribute(): ?Part
    {
        if (! isset($this->attributes['user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes(): array
    {
        return [];
    }
}

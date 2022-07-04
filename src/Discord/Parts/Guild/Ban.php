<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * A Ban is a ban on a user specific to a guild. It is also IP based.
 *
 * @see https://discord.com/developers/docs/resources/guild#ban-object
 *
 * @property string      $reason   The reason for the ban.
 * @property User        $user     The banned user.
 * @property string      $user_id
 * @property string|null $guild_id
 * @property Guild|null  $guild
 */
class Ban extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'reason',
        'user',

        // internally used
        'user_id',
        'guild_id',
    ];

    /**
     * Returns the user id of the ban.
     *
     * @return string|null
     */
    protected function getUserIdAttribute(): ?string
    {
        if (isset($this->attributes['user_id'])) {
            return $this->attributes['user_id'];
        }

        if (isset($this->attributes['user']->id)) {
            return $this->attributes['user']->id;
        }

        return null;
    }

    /**
     * Returns the guild attribute of the ban.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the user attribute of the ban.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        if (isset($this->attributes['user_id']) && $user = $this->discord->users->get('id', $this->attributes['user_id'])) {
            return $user;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'user_id' => $this->user_id,
        ];
    }
}

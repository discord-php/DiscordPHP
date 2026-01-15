<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents a user's platform.
 *
 * Undocumented.
 *
 * @since 10.40.0
 *
 * @property      string $user_id  The ID of the user.
 * @property-read User   $user     The user.
 * @property      int    $platform The platform for the user.
 */
class Platform extends Part
{
    public const PLATFORM_DESKTOP = 0;
    public const PLATFORM_MOBILE = 1;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'user_id',
        'platform',
    ];

    /**
     * Get the user attribute.
     *
     * @return User|null The user.
     */
    protected function getUserAttribute(): ?User
    {
        if (! isset($this->attributes['user_id'])) {
            return $this->discord->user;
        }

        return $this->discord->users->get('id', $this->attributes['user_id']);
    }
}

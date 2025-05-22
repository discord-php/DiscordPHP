<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * Represents a user's primary guild (clan) information.
 *
 * @link TBD
 *
 * @since 10.10.0
 *
 * @property string  $identityGuildId   The id of the user's primary clan.
 * @property bool    $identityEnabled   Whether the user is displaying their clan tag.
 * @property string  $tag               The text of the user's clan tag (max 4 characters).
 * @property string  $badge             The clan badge hash.
 *
 * @property-read string $id The identifier of the primary guild.
 */
class PrimaryGuild extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id', // @internal
        'identityGuildId',
        'identityEnabled',
        'tag',
        'badge',
    ];

    /**
     * Returns the id attribute.
     *
     * @return string The id attribute.
     */
    protected function getIdAttribute(): string
    {
        return $this->identityGuildId;
    }
}

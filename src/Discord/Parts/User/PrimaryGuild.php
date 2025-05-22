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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Represents a user's primary guild (clan) information.
 *
 * @link https://discord.com/developers/docs/resources/user#user-object-primary-guild
 *
 * @since 10.10.1
 *
 * @property string  $identity_guild_id The id of the user's primary clan.
 * @property bool    $identity_enabled  Whether the user is displaying their clan tag.
 * @property string  $tag               The text of the user's clan tag (max 4 characters).
 * @property string  $badge             The clan badge hash.
 *
 * @property-read ?string|null $id   The identifier of the primary guild.
 * @property-read ?Guild|null $guild The primary guild, if available.
 */
class PrimaryGuild extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id', // @internal
        'identity_guild_id',
        'identity_enabled',
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
    protected function getIdAttribute(): ?string
    {
        return $this->identity_guild_id ?? null;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->identity_guild_id);
    }
}

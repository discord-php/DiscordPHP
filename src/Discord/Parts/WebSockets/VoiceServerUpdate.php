<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Tells the client that the voice channel's server has changed.
 *
 * @link https://discord.com/developers/docs/topics/gateway-events#voice
 *
 * @since 4.0.0
 *
 * @property      string     $token    The new client voice token.
 * @property      string     $guild_id The unique identifier of the guild that was affected by the change.
 * @property-read Guild|null $guild    The guild affected by the change.
 * @property      ?string    $endpoint The new voice server endpoint.
 */
class VoiceServerUpdate extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'token',
        'guild_id',
        'endpoint',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }
}

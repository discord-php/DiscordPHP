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

namespace Discord\Parts\Monetization;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Represents a game server object delivered by gateway events.
 *
 * @link TBD
 *
 * @since TBD
 *
 * @property string $id
 * @property string $game_id
 * @property string $entitlement_id
 * @property string $name
 * @property string $status
 * @property string $sku_id
 * @property string $region_name
 * @property string $region_id
 * @property string $provider_url
 * @property string $provider_type
 * @property string $port
 * @property int    $players_count
 * @property string $plan_name
 * @property int    $max_players_count
 * @property string $ip
 * @property array  $game_config
 *
 * @property-read string|null $guild_id
 * @property-read Guild|null  $guild
 */
class GameServer extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'game_id',
        'entitlement_id',
        'name',
        'status',
        'sku_id',
        'region_name',
        'region_id',
        'provider_url',
        'provider_type',
        'port',
        'players_count',
        'plan_name',
        'max_players_count',
        'ip',
        'game_config',

        // internal
        'guild_id',
    ];

    /**
     * Gets the guild associated with this game server, if any.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->attributes['guild_id']);
    }
}

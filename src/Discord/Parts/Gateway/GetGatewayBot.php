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

namespace Discord\Parts\Gateway;

use Discord\Parts\Part;

/**
 * An object based on the information in Get Gateway,
 * plus additional metadata that can help during the operation of large or sharded bots.
 *
 * Unlike the Get Gateway, this route should not be cached for extended periods of time as the value is not guaranteed to be the same per-call, and changes as the bot joins/leaves guilds.
 *
 * @link https://discord.com/developers/docs/events/gateway#get-gateway-bot
 *
 * @since 10.18.0
 *
 * @property string            $url                 WSS URL that can be used for connecting to the Gateway.
 * @property int               $shards              Recommended number of shards to use when connecting.
 * @property SessionStartLimit $session_start_limit Information on the current session start limit.
 */
class GetGatewayBot extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'url',
        'shards',
        'session_start_limit',
    ];

    public function getSessionStartLimitAttribute(): SessionStartLimit
    {
        return $this->attributePartHelper('session_start_limit', SessionStartLimit::class);
    }
}

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
 * The Gateway's WSS URL, from Get Gateway, which needs no authentication.
 *
 * The URL rarely changes, so it can be cached for a long time.
 *
 * @link https://docs.discord.com/developers/events/gateway#get-gateway
 *
 * @since 10.60.0
 *
 * @property string $url WSS URL that can be used for connecting to the Gateway.
 */
class GetGateway extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'url',
    ];
}

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
 * Requests ephemeral channel data for channels in a guild. The server will send a Channel Info event in response.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#request-channel-info
 *
 * @since 10.56.5
 *
 * @property string   $guild_id The guild id to request channel info for.
 * @property string[] $fields   The fields to request. The current available fields are `status` and `voice_start_time`.
 */
class RequestChannelInfo extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'fields',
    ];
}

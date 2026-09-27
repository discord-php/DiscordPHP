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

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\ChannelInfoChannel;
use Discord\WebSockets\Event;

/**
 * Includes ephemeral data for channels in a guild. Sent in response to Request Channel Info. Sent in response to Request Channel Info.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#channel-info
 *
 * @since 10.48.0
 */
class ChannelInfo extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $channels = [];

        foreach ($data->channels ?? [] as $channelData) {
            $channels[] = $this->factory->part(ChannelInfoChannel::class, (array) $channelData, true);
        }

        return [
            'guild_id' => $data->guild_id ?? null,
            'channels' => $channels,
        ];
    }
}

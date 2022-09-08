<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway#webhooks-update
 *
 * @since 7.0.0
 */
class WebhooksUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle($data)
    {
        return yield $this->discord->guilds->cacheGet($data->guild_id)->then(function ($guild) use ($data) {
            if ($guild) {
                return $guild->channels->cacheGet($data->channel_id)->then(fn ($channel) => [$guild, $channel]);
            }

            return [(object) ['id' => $data->guild_id], (object) ['id' => $data->channel_id]];
        });
    }
}

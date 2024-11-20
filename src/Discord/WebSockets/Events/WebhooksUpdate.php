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

use Discord\Parts\Channel\Channel;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#webhooks-update
 *
 * @since 7.0.0
 */
class WebhooksUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if (! $channel = yield $guild->channels->cacheGet($data->channel_id)) {
                /** @var Channel */
                foreach ($guild->channels as $parent) {
                    /** @var ?Thread */
                    if ($thread = yield $parent->threads->cacheGet($data->channel_id)) {
                        $channel = $thread;
                        break;
                    }
                }
            }

            return [$guild, $channel ?? (object) ['id' => $data->channel_id]];
        }

        return [(object) ['id' => $data->guild_id], (object) ['id' => $data->channel_id]];
    }
}

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
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#thread-delete
 *
 * @since 7.0.0
 */
class ThreadDelete extends Event
{
    public function handle($data)
    {
        $threadPart = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if ($parent = yield $guild->channels->cacheGet($data->parent_id)) {
                $threadPart = yield $parent->threads->cachePull($data->id);
            }
        }

        return $threadPart ?? $data;
    }
}

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
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#thread-update
 *
 * @since 7.0.0
 */
class ThreadUpdate extends Event
{
    public function handle($data)
    {
        $threadPart = $oldThread = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if ($parent = yield $guild->channels->cacheGet($data->parent_id)) {
                /** @var ?Thread */
                if ($oldThread = yield $parent->threads->cacheGet($data->id)) {
                    // Swap
                    $threadPart = $oldThread;
                    $oldThread = clone $oldThread;

                    $threadPart->fill((array) $data);
                }
            }
        }

        if ($threadPart === null) {
            /** @var Thread */
            $threadPart = $this->factory->part(Thread::class, (array) $data, true);
        }

        if (isset($parent)) {
            $parent->threads->set($data->id, $threadPart);
        }

        return [$threadPart, $oldThread];
    }
}

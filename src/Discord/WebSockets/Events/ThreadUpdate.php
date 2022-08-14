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

use Discord\Helpers\Deferred;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#thread-update
 */
class ThreadUpdate extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        coroutine(function ($data) {
            $threadPart = $oldThread = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Channel */
                if ($parent = yield $guild->channels->cacheGet($data->parent_id)) {
                    /** @var ?Thread */
                    if ($oldThread = $parent->threads[$data->id]) {
                        // Swap
                        $threadPart = $oldThread;
                        $oldThread = clone $oldThread;

                        $threadPart->fill((array) $data);
                    }
                }
            }

            if ($threadPart === null) {
                /** @var Thread */
                $threadPart = $this->factory->create(Thread::class, $data, true);
            }

            if (isset($parent)) {
                yield $parent->threads->cache->set($data->id, $threadPart);
            }

            return [$threadPart, $oldThread];
        }, $data)->then([$deferred, 'resolve']);
    }
}

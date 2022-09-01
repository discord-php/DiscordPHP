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
 * @link https://discord.com/developers/docs/topics/gateway#thread-create
 *
 * @since 7.0.0
 */
class ThreadCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle($data)
    {
        /** @var Thread */
        $threadPart = $this->factory->part(Thread::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if ($parent = yield $guild->channels->cacheGet($data->parent_id)) {
                yield $parent->threads->cache->set($data->id, $threadPart);
            }
        }

        return $threadPart;
    }
}

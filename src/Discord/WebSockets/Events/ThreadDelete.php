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
use Discord\WebSockets\Event;

use function React\Async\coroutine;

/**
 * @link https://discord.com/developers/docs/topics/gateway#thread-delete
 *
 * @since 7.0.0
 *
 * @todo update docs parameter
 */
class ThreadDelete extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        coroutine(function ($data) {
            $threadPart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Channel */
                if ($parent = yield $guild->channels->cacheGet($data->parent_id)) {
                    $threadPart = yield $parent->threads->cachePull($data->id);
                }
            }

            return $threadPart ?? $data;
        }, $data)->then([$deferred, 'resolve']);
    }
}

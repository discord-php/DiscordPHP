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
use Discord\Parts\Thread\Member;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#thread-member-update
 */
class ThreadMemberUpdate extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        coroutine(function ($data) {
            $memberPart = $this->factory->create(Member::class, $data, true);

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var Channel */
                foreach ($guild->channels as $channel) {
                    /** @var ?Thread */
                    if ($thread = yield $channel->threads->cacheGet($data->id)) {
                        yield $thread->members->cache->set($data->user_id, $memberPart);
                        break;
                    }
                }
            }

            return $memberPart;
        }, $data)->then([$deferred, 'resolve']);
    }
}

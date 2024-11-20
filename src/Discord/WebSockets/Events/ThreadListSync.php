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

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#thread-list-sync
 *
 * @since 7.0.0
 */
class ThreadListSync extends Event
{
    public function handle($data)
    {
        $threadParts = Collection::for(Thread::class);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            foreach ($data->channel_ids as $channel_id) {
                /** @var ?Channel[] */
                $channels[$channel_id] = yield $guild->channels->cacheGet($channel_id);
            }

            foreach ($data->threads as $thread) {
                /** @var Thread */
                $threadPart = $this->factory->part(Thread::class, (array) $thread, true);
                /** @var ?Channel */
                if ($channel = $channels[$thread->parent_id] ?? null) {
                    /** @var ?Thread */
                    if ($oldThread = yield $channel->threads->cacheGet($thread->id)) {
                        $oldThread->fill((array) $thread);
                        $threadPart = $oldThread;
                    }
                    $channel->threads->set($thread->id, $threadPart);
                }
                $threadParts->pushItem($threadPart);
            }

            foreach ($data->members as $member) {
                /** @var ?Thread */
                if ($threadPart = $threadParts[$member->id] ?? null) {
                    $threadPart->members->set($member->user_id, $threadPart->members->create((array) $member + ['guild_id' => $data->guild_id], true));
                }
            }
        }

        return $threadParts;
    }
}

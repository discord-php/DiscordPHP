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
use Discord\Helpers\Deferred;
use Discord\Parts\Thread\Member;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @see https://discord.com/developers/docs/topics/gateway#thread-list-sync
 */
class ThreadListSync extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);
        $threads = Collection::for(Thread::class);
        $members = (array) $data->members;

        foreach ($data->threads as $thread) {
            if ($channel = $guild->channels->get('id', $thread->parent_id)) {
                if ($threadPart = $channel->threads->get('id', $thread->id)) {
                    $threadPart->fill((array) $thread);
                }
            }

            if (! $threadPart) {
                /** @var Thread */
                $threadPart = $this->factory->create(Thread::class, $thread, true);
                if ($channel = $threadPart->parent) {
                    $channel->threads->pushItem($threadPart);
                }
            }

            foreach ($members as $member) {
                if ($member->id == $thread->id) {
                    $threadPart->members->pushItem($this->factory->create(Member::class, $member, true));
                }
            }

            $threads->pushItem($threadPart);
        }

        $deferred->resolve($threads);
    }
}

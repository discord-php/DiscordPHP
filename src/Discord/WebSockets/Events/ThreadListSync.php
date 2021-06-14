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
use Discord\Parts\Thread\Member;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

class ThreadListSync extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);
        $members = (array) $data->members;

        foreach ($data->threads as $thread) {
            /** @var Thread */
            $thread = $this->factory->create(Thread::class, $thread, true);

            foreach ($members as $member) {
                if ($member->id == $thread->id) {
                    $thread->members->push($this->factory->create(Member::class, $members[$thread->id], true));
                    break;
                }
            }

            $guild->threads->push($thread);
        }

        $deferred->resolve();
    }
}

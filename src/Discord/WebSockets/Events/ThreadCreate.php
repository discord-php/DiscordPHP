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

/**
 * @see https://discord.com/developers/docs/topics/gateway#thread-create
 */
class ThreadCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data)
    {
        /** @var Thread */
        $thread = $this->factory->create(Thread::class, $data, true);

        // Ignore threads that have already been added
        if ($parent = $thread->parent) {
            if ($parent->threads->get('id', $thread->id)) {
                return;
            }

            foreach ($data->members ?? [] as $member) {
                $member = $this->factory->create(Member::class, $member, true);
                $thread->members->push($member);
            }

            if ($data->member ?? null) {
                $member = $this->factory->create(Member::class, $data->member, true);
                $thread->members->push($member);
            }

            $parent->threads->push($thread);
        }
        
        $deferred->resolve($thread);
    }
}

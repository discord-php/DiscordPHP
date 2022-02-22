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
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @see https://discord.com/developers/docs/topics/gateway#thread-update
 */
class ThreadUpdate extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        $threadPart = $oldThread = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($parent = $guild->channels->get('id', $data->parent_id)) {
                if ($oldThread = $parent->threads->get('id', $data->id)) {
                    // Swap
                    $threadPart = $oldThread;
                    $oldThread = clone $oldThread;

                    $threadPart->fill((array) $data);
                }
            }
        }

        if (! $threadPart) {
            /** @var Thread */
            $threadPart = $this->factory->create(Thread::class, $data, true);
            if ($parent = $threadPart->parent) {
                $parent->threads->pushItem($threadPart);
            }
        }

        $deferred->resolve([$threadPart, $oldThread]);
    }
}

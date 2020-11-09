<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\all as All;

class MessageDeleteBulk extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $promises = [];

        foreach ($data->ids as $id) {
            $promise = new Deferred();
            $event = new MessageDelete($this->http, $this->factory, $this->discord);
            $event->handle($promise, (object) ['id' => $id, 'channel_id' => $data->channel_id, 'guild_id' => $data->guild_id]);

            $promises[] = $promise->promise();
        }

        /** @var ExtendedPromiseInterface */
        $allPromise = All($promises);
        $allPromise->done(function ($messages) use ($deferred) {
            $deferred->resolve($messages);
        });
    }
}

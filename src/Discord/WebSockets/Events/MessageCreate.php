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

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class MessageCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $message = $this->factory->create(Message::class, $data, true);

        if ($this->discord->options['storeMessages']) {
            if ($channel = $message->channel) {
                $channel->messages->offsetSet($message->id, $message);
                if ($guild = $channel->guild) {
                    $guild->channels->offsetSet($channel->id, $channel);
                    $this->discord->guilds->offsetSet($guild->id, $guild);
                }
            }
        }

        $deferred->resolve($message);
    }
}

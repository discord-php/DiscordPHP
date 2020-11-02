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
use Discord\Helpers\Deferred;

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
                if ($guild = $channel->guild) {
                    $channel->messages->push($message);
                    $guild->channels->push($channel);
                    $this->discord->guilds->push($guild);
                }
            }
        }

        $deferred->resolve($message);
    }
}

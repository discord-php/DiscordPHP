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

class MessageUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $messagePart = $this->factory->create(Message::class, $data, true);
        $channel = $messagePart->channel;
        $oldMessage = $channel->messages->get('id', $messagePart->id);

        if (is_null($oldMessage)) {
            $newMessage = $messagePart;
        } else {
            $newMessage = $this->factory->create(Message::class, array_merge($oldMessage->getRawAttributes(), $messagePart->getRawAttributes()), true);
        }

        $channel->messages->push($newMessage);
        $this->discord->guilds->get('id', $channel->guild_id)->channels->push($channel);

        $deferred->resolve([$messagePart, $oldMessage]);
    }
}

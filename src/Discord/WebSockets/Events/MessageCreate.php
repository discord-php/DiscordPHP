<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Message;
use Discord\Repository\Channel\MessageRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class MessageCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $messagePart = $this->factory->create(Message::class, $data, true);

        if ($this->discord->options['storeMessages']) {
            $messages = $this->discord->getRepository(
                MessageRepository::class,
                $messagePart->channel_id,
                'messages',
                ['channel_id' => $messagePart->channel_id]
            );
            $messages->push($messagePart);
        }

        $deferred->resolve($messagePart);
    }
}

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

class MessageUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $messagePart = $this->factory->create(Message::class, $data, true);
        $old         = null;

        if ($this->discord->options['storeMessages']) {
            if ($this->discord->private_channels->has($data->channel_id)) {
                $messages = $this->discord->private_channels->offsetGet($data->channel_id)->messages;
            } else {
                $messages = $this->discord->getRepository(
                    MessageRepository::class,
                    $messagePart->channel_id,
                    'messages',
                    ['channel_id' => $messagePart->channel_id]
                );
            }

            if ($messages->has($messagePart->id)) {
                $old        = $messages->offsetGet($messagePart->id);
                $newMessage = $this->factory->create(Message::class, array_merge($old->getRawAttributes(), $messagePart->getRawAttributes()), true);
            } else {
                $newMessage = $messagePart;
            }
            $messages->offsetSet($newMessage->id, $newMessage);
        }

        $deferred->resolve([$messagePart, $old]);
    }
}

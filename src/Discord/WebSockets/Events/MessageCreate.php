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
use Discord\WebSockets\Event;
use React\Promise\Deferred;

/**
 * Event that is emitted when `MESSAGE_CREATE` is fired.
 */
class MessageCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, array $data)
    {
        /** @var Message $data */
        $data = $this->partFactory->create(Message::class, $data, true);

        $data->getFullChannelAttribute()->then(function ($channel) use ($data, $deferred) {
            $data->setAttribute('channel', $channel);

            $this->cache->set("message.{$data->id}", $data);

            foreach ($this->discord->guilds as $index => $guild) {
                foreach ($guild->channels as $cindex => $channel) {
                    if ($channel->id == $data->channel_id) {
                        $channel->messages[$data->id] = $data;

                        break 2;
                    }
                }
            }

            $deferred->resolve($data);
        });
    }
}

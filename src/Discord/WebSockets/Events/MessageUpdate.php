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
 * Event that is emitted when `MESSAGE_UPDATE` is fired.
 */
class MessageUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $data = $this->partFactory->create(Message::class, $data, true);

        $this->cache->set("message.{$data->id}", $data);

        foreach ($this->discord->guilds as $index => $guild) {
            foreach ($guild->channels as $cindex => $channel) {
                if ($channel->id == $data->channel_id) {
                    $message = $channel->messages->pull($data->id);

                    if (!isset($data->content)) {
                        $message = $this->partFactory->create(
                            Message::class,
                            array_merge((array) $message, (array) $data),
                            true
                        );
                        $channel->messages->push($message);
                    } else {
                        $channel->messages->push($data);
                    }

                    break;
                }
            }

            $this->discord->guilds[$index] = $guild;
        }

        $deferred->resolve($data);
    }
}

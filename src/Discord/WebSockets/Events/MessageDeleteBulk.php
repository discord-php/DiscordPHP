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

use Discord\Repository\Channel\MessageRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class MessageDeleteBulk extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        if ($this->discord->options['storeMessages']) {
            if ($this->discord->private_channels->has($data->channel_id)) {
                $messages = $this->discord->private_channels->offsetGet($data->channel_id)->messages;
            } else {
                $messages = $this->discord->getRepository(
                    MessageRepository::class,
                    $data->channel_id,
                    'messages',
                    ['channel_id' => $data->channel_id]
                );
            }

            foreach ($data->ids as $messageid) {
                if ($channel->messages->has($messageid)) {
                    $channel->messages->pull($messageid);
                }
            }
        }

        $deferred->resolve($data);
    }
}

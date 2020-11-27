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

class MessageReactionRemoveEmoji extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        if ($channel = $this->discord->getChannel($data->channel_id)) {
            if ($message = $channel->messages->offsetGet($data->message_id)) {
                foreach ($message->reactions as $key => $react) {
                    if ($react->id == $data->id) {
                        unset($message->reactions[$key]);
                    }
                }
            }
        }

        $deferred->resolve($data);
    }
}

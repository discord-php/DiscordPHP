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

class MessageDelete extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $message = null;

        if (! isset($data->guild_id)) {
            if ($channel = $this->discord->private_channels->offsetGet($data->channel_id)) {
                $message = $channel->messages->pull($data->id);
                $this->discord->private_channels->offsetSet($channel->id, $channel);
            }
        } else {
            if ($guild = $this->discord->guilds->offsetGet($data->guild_id)) {
                if ($channel = $guild->channels->offsetGet($data->channel_id)) {
                    $message = $channel->messages->pull($data->id);
                    $guild->channels->offsetSet($channel->id, $channel);
                    $this->discord->guilds->offsetSet($guild->id, $guild);
                }
            }
        }

        $deferred->resolve(is_null($message) ? $data : $message);
    }
}

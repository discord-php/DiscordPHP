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
use React\Promise\Deferred;

class MessageDelete extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($channel = $guild->channels->get('id', $data->channel_id)) {
                $message = $channel->messages->pull($data->id);
                $guild->channels->push($channel);
                $this->discord->guilds->push($guild);
            }
        }

        $deferred->resolve(is_null($message) ? $data : $message);
    }
}

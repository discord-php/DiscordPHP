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

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

/**
 * Event that is emitted when `CHANNEL_CREATE` is fired.
 */
class ChannelCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $data = $this->partFactory->create(Channel::class, $data, true);
        $this->cache->set('channel.'.$data->id, $data);

        if (! $data->is_private) {
            foreach ($this->discord->guilds as $index => $guild) {
                if ($guild->id === $data->guild_id) {
                    $guild->channels->push($data);

                    break;
                }
            }
        } else {
            $this->discord->privateChannels->push($data);
        }

        $deferred->resolve($data);
    }
}

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
 * Event that is emitted when `CHANNEL_DELETE` is fired.
 */
class ChannelDelete extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $data = $this->partFactory->create(Channel::class, $data, true);
        $this->cache->remove('channel.'.$data->id);

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->channels as $cindex => $channel) {
                    if ($channel->id == $data->id) {
                        $guild->channels->pull($index);

                        break;
                    }
                }
            }
        }

        $deferred->resolve($data);
    }
}

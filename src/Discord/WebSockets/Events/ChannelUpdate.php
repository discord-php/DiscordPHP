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

class ChannelUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $channel = $this->factory->create(Channel::class, $data, true);

        if ($channel->is_private) {
            $old = $this->discord->private_channels->has($channel->id) ? $this->discord->private_channels->offsetGet($channel->id) : null;
            $this->discord->private_channels->offsetSet($channel->id, $channel);
        } else {
            $guild = $this->discord->guilds->offsetGet($channel->guild_id);
            $old   = $guild->channels->has($channel->id) ? $guild->channels->offsetGet($channel->id) : null;
            $guild->channels->offsetSet($channel->id, $channel);
        }

        $deferred->resolve([$channel, $old]);
    }
}

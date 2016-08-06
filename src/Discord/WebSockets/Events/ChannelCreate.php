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

class ChannelCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $channel = $this->factory->create(Channel::class, $data, true);

        if (array_search($channel->type, [Channel::TYPE_TEXT, Channel::TYPE_VOICE]) === false) {
            $this->discord->private_channels->push($channel);
        } else {
            $guild = $this->discord->guilds->get('id', $channel->guild_id);
            $guild->channels->push($channel);
        }

        $deferred->resolve($channel);
    }
}

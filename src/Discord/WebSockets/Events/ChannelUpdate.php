<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class ChannelUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $channel = $this->factory->create(Channel::class, $data, true);

        if ($channel->is_private) {
            $old = $this->discord->private_channels->get('id', $channel->id);
            $this->discord->private_channels->push($channel);
        } elseif ($guild = $this->discord->guilds->get('id', $channel->guild_id)) {
            $old = $guild->channels->get('id', $channel->id);
            $guild->channels->push($channel);
            $this->discord->guilds->push($guild);
        }

        $deferred->resolve([$channel, $old]);
    }
}

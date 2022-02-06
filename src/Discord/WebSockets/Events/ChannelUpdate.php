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

/**
 * @see https://discord.com/developers/docs/topics/gateway#channel-update
 */
class ChannelUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $oldChannel = null;

        /** @var Channel */
        $channelPart = $this->factory->create(Channel::class, $data, true);

        if ($channelPart->is_private) {
            if (! $oldChannel = $this->discord->private_channels->get('id', $data->id)) {
                $this->discord->private_channels->pushItem($channelPart);
            }
        } elseif ($guild = $channelPart->guild) {
            if (! $oldChannel = $guild->channels->get('id', $data->id)) {
                $guild->channels->pushItem($channelPart);
            }
        }

        if ($oldChannel) {
            // Swap
            $channelPart = $oldChannel;
            $oldChannel = clone $oldChannel;

            $channelPart->fill((array) $data);
        }

        $deferred->resolve([$channelPart, $oldChannel]);
    }
}

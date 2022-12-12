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

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#channel-update
 *
 * @since 2.1.3
 */
class ChannelUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldChannel = $repository = null;

        /** @var Channel */
        $channelPart = $this->factory->part(Channel::class, (array) $data, true);

        if ($channelPart->is_private) {
            /** @var ?Channel */
            if (! $oldChannel = yield $this->discord->private_channels->cacheGet($data->id)) {
                $repository = $this->discord->private_channels;
            }
        } elseif ($guild = $channelPart->guild) {
            /** @var ?Channel */
            if (! $oldChannel = yield $guild->channels->cacheGet($data->id)) {
                $repository = $guild->channels;
            }
        }

        if ($oldChannel) {
            // Swap
            $channelPart = $oldChannel;
            $oldChannel = clone $oldChannel;

            $channelPart->fill((array) $data);
        }

        if ($repository) {
            $repository->set($data->id, $channelPart);
        }

        return [$channelPart, $oldChannel];
    }
}

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

use function React\Async\coroutine;

/**
 * @link https://discord.com/developers/docs/topics/gateway#channel-update
 */
class ChannelUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $oldChannel = $repository = null;

            /** @var Channel */
            $channelPart = $this->factory->create(Channel::class, $data, true);

            if ($channelPart->is_private) {
                /** @var ?Channel */
                if (! $oldChannel = $this->discord->private_channels[$data->id]) {
                    $repository = $this->discord->private_channels;
                }
            } elseif ($guild = $channelPart->guild) {
                /** @var ?Channel */
                if (! $oldChannel = $guild->channels[$data->id]) {
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
                yield $repository->cache->set($data->id, $channelPart);
            }

            return [$channelPart, $oldChannel];
        }, $data)->then([$deferred, 'resolve']);
    }
}

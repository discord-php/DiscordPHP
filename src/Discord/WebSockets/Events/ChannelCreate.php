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
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#channel-create
 */
class ChannelCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            /** @var Channel */
            $channelPart = $this->factory->create(Channel::class, $data, true);

            if ($channelPart->is_private) {
                yield $this->discord->private_channels->cache->set($data->id, $channelPart);
            } else {
                /** @var ?Guild */
                if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                    yield $guild->channels->cache->set($data->id, $channelPart);
                }
            }

            return $channelPart;
        }, $data)->then([$deferred, 'resolve']);
    }
}

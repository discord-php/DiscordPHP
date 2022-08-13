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
 * @see https://discord.com/developers/docs/topics/gateway#channel-delete
 */
class ChannelDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $channelPart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var Channel */
                if ($channelPart = yield $guild->channels->cachePull($data->id)) {
                    $channelPart->fill((array) $data);
                    $channelPart->created = false;
                }
            }

            return $channelPart ?? $this->factory->create(Channel::class, $data);;
        }, $data)->then([$deferred, 'resolve']);
    }
}

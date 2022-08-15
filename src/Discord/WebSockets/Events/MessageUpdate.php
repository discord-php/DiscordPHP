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

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#message-update
 */
class MessageUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            /** @var Message */
            $messagePart = $oldMessagePart = null;

            if (isset($data->guild_id)) {
                /** @var ?Guild */
                if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                    /** @var ?Channel */
                    $channel = yield $guild->channels->cacheGet($data->channel_id);
                }
            }

            if (isset($channel)) {
                /** @var ?Message */
                if ($oldMessagePart = yield $channel->messages->cacheGet($data->id)) {
                    // Swap
                    $messagePart = $oldMessagePart;
                    $oldMessagePart = clone $oldMessagePart;

                    $messagePart->fill((array) $data);
                }
            }

            if ($oldMessagePart === null) {
                /** @var Message */
                $messagePart = $this->factory->create(Message::class, $data, true);
            }

            if (isset($channel) && ($oldMessagePart || $this->discord->options['storeMessages'])) {
                yield $channel->messages->cache->set($data->id, $messagePart);
            }

            return [$messagePart, $oldMessagePart];
        }, $data)->then([$deferred, 'resolve']);
    }
}

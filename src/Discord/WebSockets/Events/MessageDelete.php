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

use Discord\WebSockets\Event;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#message-delete
 *
 * @since 2.1.3
 */
class MessageDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $messagePart = null;

        if (! isset($data->guild_id)) {
            /** @var ?Channel */
            if ($channel = yield $this->discord->private_channels->cacheGet($data->channel_id)) {
                /** @var ?Message */
                $messagePart = yield $channel->messages->cachePull($data->id);
            }
        } else {
            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Channel */
                if (! $channel = yield $guild->channels->cacheGet($data->channel_id)) {
                    /** @var Channel */
                    foreach ($guild->channels as $parent) {
                        /** @var ?Thread */
                        if ($thread = yield $parent->threads->cacheGet($data->channel_id)) {
                            $channel = $thread;
                            break;
                        }
                    }
                }

                if (isset($channel)) {
                    /** @var ?Message */
                    $messagePart = yield $channel->messages->cachePull($data->id);
                    if ($channel instanceof Thread) {
                        $channel->message_count--;
                    }
                }
            }
        }

        if ($messagePart) {
            $messagePart->reactions->cache->clear();
        }

        return $messagePart ?? $data;
    }
}

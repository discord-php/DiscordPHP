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
 * @see https://discord.com/developers/docs/topics/gateway#message-create
 */
class MessageCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            /** @var Message */
            $messagePart = $this->factory->create(Message::class, $data, true);

            if ($messagePart->is_private) {
                /** @var Channel */
                $channel = $this->factory->create(Channel::class, [
                    'id' => $data->channel_id,
                    'type' => Channel::TYPE_DM,
                    'last_message_id' => $data->id,
                    'recipients' => [$data->author],
                ], true);

                yield $this->discord->private_channels->cache->set($data->channel_id, $channel);
            }

            if (isset($data->guild_id)) {
                /** @var ?Guild */
                $guild = yield $this->discord->guilds->cacheGet($data->guild_id);

                if (! isset($channel)) {
                    /** @var ?Channel */
                    $channel = yield $guild->channels->cacheGet($data->channel_id);
                }
            }

            if ($this->discord->options['storeMessages'] && (isset($channel) || $channel = $messagePart->channel)) {
                yield $channel->messages->cache->set($data->id, $messagePart);
            }

            if (isset($data->author) && ! isset($data->webhook_id)) {
                $this->cacheUser($data->author);
            }

            if (isset($data->interaction->user)) {
                $this->cacheUser($data->interaction->user);
            }

            return $messagePart;
        }, $data)->then([$deferred, 'resolve']);
    }
}

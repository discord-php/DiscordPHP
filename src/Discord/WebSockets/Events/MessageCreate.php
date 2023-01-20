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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Intents;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#message-create
 *
 * @since 2.1.3
 */
class MessageCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Message */
        $messagePart = $this->factory->part(Message::class, (array) $data, true);

        if ($messagePart->is_private) {
            /** @var Channel */
            $channel = $this->factory->part(Channel::class, [
                'id' => $data->channel_id,
                'type' => Channel::TYPE_DM,
                'last_message_id' => $data->id,
                'recipients' => [$data->author],
            ], true);

            $this->discord->private_channels->set($data->channel_id, $channel);
        }

        /** @var ?Guild */
        if (isset($data->guild_id) && $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if (! isset($channel) && ! $channel = yield $guild->channels->cacheGet($data->channel_id)) {
                /** @var Channel */
                foreach ($guild->channels as $parent) {
                    /** @var ?Thread */
                    if ($thread = yield $parent->threads->cacheGet($data->channel_id)) {
                        $thread->message_count++;
                        $thread->total_message_sent++;
                        $channel = $thread;
                        break;
                    }
                }
            }
        }

        if ($this->discord->options['storeMessages'] && (isset($channel) || $channel = $messagePart->channel)) {
            // Only cache if message intent is enabled or message was sent by the bot or message is not cached
            if (($this->discord->options['intents'] & Intents::MESSAGE_CONTENT) || $data->author->id == $this->discord->id || ! (yield $channel->messages->cache->has($data->id))) {
                $channel->messages->set($data->id, $messagePart);
            }
        }

        if (isset($channel)) {
            $channel->last_message_id = $data->id;
        }

        if (isset($data->author) && ! isset($data->webhook_id)) {
            if (isset($data->member) && $guild) {
                $this->cacheMember($guild->members, (array) $data->member + ['user' => $data->author]);
            }
            $this->cacheUser($data->author);
        }

        foreach ($data->mentions ?? [] as $user) {
            if (isset($user->member) && $guild) {
                $this->cacheMember($guild->members, (array) $user->member + ['user' => $user]);
            }
            $this->cacheUser($user);
        }

        if (isset($data->interaction->user)) {
            $this->cacheUser($data->interaction->user);
        }

        return $messagePart;
    }
}

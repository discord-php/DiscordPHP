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
 * @link https://discord.com/developers/docs/topics/gateway-events#message-update
 *
 * @since 2.1.3
 */
class MessageUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Message */
        $messagePart = $oldMessagePart = null;

        /** @var ?Guild */
        if (isset($data->guild_id) && $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
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
        }

        if (isset($channel)) {
            /** @var ?Message */
            if ($oldMessagePart = yield $channel->messages->cacheGet($data->id)) {
                // Swap
                $messagePart = $oldMessagePart;
                $oldMessagePart = clone $oldMessagePart;

                $messagePart->fill((array) $data);

                // Deal with empty message content intent
                if (! ($this->discord->options['intents'] & Intents::MESSAGE_CONTENT) && ($data->author->id ?? $oldMessagePart->user_id) != $this->discord->id) {
                    $cacheMessagePart = clone $oldMessagePart;
                    // Ignore intent required fields
                    $cacheMessagePart->fill(array_filter((array) $data, fn ($value, $key) => ! in_array($key, ['content', 'embeds', 'attachments', 'components']), ARRAY_FILTER_USE_BOTH));
                }
            }
        }

        if ($oldMessagePart === null && isset($data->type)) { // Message has type means not partial
            /** @var Message */
            $messagePart = $this->factory->part(Message::class, (array) $data, true);
        }

        if (isset($channel) && ($oldMessagePart || $this->discord->options['storeMessages']) && $setMessageData = $cacheMessagePart ?? $messagePart) { // Skip partial messages
            $channel->messages->set($data->id, $setMessageData);
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

        return [$messagePart ?? $data, $oldMessagePart];
    }
}

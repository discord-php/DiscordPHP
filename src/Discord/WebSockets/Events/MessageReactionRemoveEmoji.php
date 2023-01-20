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
use Discord\Parts\WebSockets\MessageReaction;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#message-reaction-remove-emoji
 *
 * @since 5.0.0
 */
class MessageReactionRemoveEmoji extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var ?Guild */
        if (isset($data->guild_id) && $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if (! $channel = yield $guild->channels->cacheGet($data->channel_id)) {
                /** @var Channel */
                foreach ($guild->channels as $channel) {
                    /** @var ?Thread */
                    if ($thread = yield $channel->threads->cacheGet($data->channel_id)) {
                        $channel = $thread;
                        break;
                    }
                }
            }
        } else {
            /** @var ?Channel */
            $channel = yield $this->discord->private_channels->cacheGet($data->channel_id);
        }

        $reaction = new MessageReaction($this->discord, (array) $data, true);

        /** @var ?Message */
        if (isset($channel) && $message = yield $channel->messages->cacheGet($data->message_id)) {
            yield $message->reactions->cache->delete($reaction->reaction_id);
        }

        return $reaction;
    }
}

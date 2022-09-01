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

use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Reaction;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;

/**
 * @link https://discord.com/developers/docs/topics/gateway#message-reaction-add
 *
 * @since 4.0.4
 */
class MessageReactionAdd extends Event
{
    /**
     * @inheritdoc
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
        if ($channel && $message = yield $channel->messages->cacheGet($data->message_id)) {
            $addedReaction = false;

            /** @var Reaction */
            foreach ($message->reactions as $react) {
                if ($react->id == $reaction->reaction_id) {
                    ++$react->count;

                    if ($reaction->user_id == $this->discord->id) {
                        $react->me = true;
                    }

                    $addedReaction = true;
                    $reaction = $react;
                    break;
                }
            }

            // New reaction added
            if (! $addedReaction) {
                $reaction->count = 1;
                $reaction->me = $data->user_id == $this->discord->id;
            }

            yield $message->reactions->cache->set($reaction->reaction_id, $reaction);
        }

        if (isset($data->member->user)) {
            $this->cacheUser($data->member->user);
        }

        return $reaction;
    }
}

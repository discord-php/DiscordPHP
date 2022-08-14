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
use Discord\Helpers\Deferred;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Reaction;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#message-reaction-add
 */
class MessageReactionAdd extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $reaction = new MessageReaction($this->discord, (array) $data, true);

            /** @var ?Guild */
            if (isset($data->guild_id) && $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                $channels = $guild->channels;
                /** @var ?Channel */
                if (! $channel = yield $channels->cacheGet($data->channel_id)) {
                    /** @var Channel */
                    foreach ($channels as $channel) {
                        /** @var ?Thread */
                        if ($thread = yield $channel->threads->cacheGet($data->channel_id)) {
                            $channel = $thread;
                            break;
                        }
                    }
                }
            }

            /** @var ?Message */
            if (isset($channel) && $message = yield $channel->messages->cacheGet($data->message_id)) {
                $addedReaction = false;
                $reactions = $message->reactions;

                /** @var Reaction */
                foreach ($reactions as $id => $react) {
                    if ($id == $reaction->reaction_id) {
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

                yield $reactions->cache->set($reaction->reaction_id, $reaction);
            }

            if (isset($data->member->user)) {
                $this->cacheUser($data->member->user);
            }

            return $reaction;
        }, $data)->then([$deferred, 'resolve']);
    }
}

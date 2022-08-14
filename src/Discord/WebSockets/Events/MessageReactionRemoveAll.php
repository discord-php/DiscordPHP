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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Thread;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#message-reaction-remove-all
 */
class MessageReactionRemoveAll extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
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

            $reaction = new MessageReaction($this->discord, (array) $data, true);

            /** @var ?Message */
            if (isset($channel) && $message = yield $channel->messages->cacheGet($data->message_id)) {
                $message->reactions->clear();
            }

            return $reaction;
        }, $data)->then([$deferred, 'resolve']);
    }
}

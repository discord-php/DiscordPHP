<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class MessageReactionRemove extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $reaction = new MessageReaction($this->discord, (array) $data, true);

        if ($channel = $reaction->channel) {
            if ($message = $channel->messages->offsetGet($reaction->message_id)) {
                $reactions = [];
                $rawReactions = $message->getRawAttributes()['reactions'] ?? [];

                foreach ($rawReactions as $react) {
                    if ($react['emoji']['name'] == $reaction->emoji->name) {
                        --$react['count'];

                        if ($reaction->user_id == $this->discord->id) {
                            $react['me'] = false;
                        }
                    }

                    if ($react['count'] > 0) {
                        $reactions[] = $react;
                    }
                }

                $message->reactions = $reactions;
            }
        }

        $deferred->resolve($reaction);
    }
}

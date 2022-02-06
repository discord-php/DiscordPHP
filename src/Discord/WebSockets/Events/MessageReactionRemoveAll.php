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
        $reaction = new MessageReaction($this->discord, (array) $data, true);

        if ($channel = $reaction->channel) {
            if ($message = $channel->messages->offsetGet($reaction->message_id)) {
                $message->reactions->clear();
            }
        }

        $deferred->resolve($reaction);
    }
}

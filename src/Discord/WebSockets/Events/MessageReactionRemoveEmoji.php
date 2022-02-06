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
use Discord\Helpers\Deferred;
use Discord\Parts\WebSockets\MessageReaction;

/**
 * @see https://discord.com/developers/docs/topics/gateway#message-reaction-remove-emoji
 */
class MessageReactionRemoveEmoji extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $reaction = new MessageReaction($this->discord, (array) $data, true);

        if ($message = $reaction->message) {
            $react = $reaction->emoji->toReactionString();
            if ($message->reactions->offsetExists($react)) {
                $message->reactions->offsetUnset($react);
            }
        }

        $deferred->resolve($reaction);
    }
}

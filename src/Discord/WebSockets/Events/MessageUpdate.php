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

class MessageUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $messagePart = $this->factory->create(Message::class, $data, true);
        $oldMessage = null;

        if ($channel = $messagePart->channel) {
            if ($oldMessage = $channel->messages->get('id', $messagePart->id)) {
                $messagePart = $this->factory->create(Message::class, array_merge($oldMessage->getRawAttributes(), $messagePart->getRawAttributes()), true);
            }

            $channel->messages->offsetSet($messagePart->id, $messagePart);

            if ($guild = $this->discord->guilds->get('id', $channel->guild_id)) {
                $guild->channels->offsetSet($channel->id, $channel);
                $this->discord->guilds->offsetSet($guild->id, $guild);
            }
        }

        $deferred->resolve([$messagePart, $oldMessage]);
    }
}

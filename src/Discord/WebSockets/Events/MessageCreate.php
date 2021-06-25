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
use Discord\Parts\Channel\Channel;

class MessageCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var Message */
        $message = $this->factory->create(Message::class, $data, true);

        // assume it is a private channel
        if ($message->channel === null) {
            $channel = $this->factory->create(Channel::class, [
                'id' => $message->channel_id,
                'type' => Channel::TYPE_DM,
                'last_message_id' => $message->id,
                'recipients' => [$message->author],
            ], true);

            $this->discord->private_channels->push($channel);
        }

        if ($this->discord->options['storeMessages']) {
            if ($channel = $message->channel) {
                if ($guild = $channel->guild) {
                    $channel->messages->push($message);
                    $guild->channels->push($channel);
                    $this->discord->guilds->push($guild);
                }
            }
        }

        $deferred->resolve($message);
    }
}

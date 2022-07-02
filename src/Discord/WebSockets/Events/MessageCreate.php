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

/**
 * @see https://discord.com/developers/docs/topics/gateway#message-create
 */
class MessageCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var Message */
        $messagePart = $this->factory->create(Message::class, $data, true);

        // assume it is a private channel
        if (! $messagePart->guild && $messagePart->channel->type == Channel::TYPE_DM) {
            /** @var Channel */
            $channel = $this->factory->create(Channel::class, [
                'id' => $messagePart->channel_id,
                'type' => Channel::TYPE_DM,
                'last_message_id' => $messagePart->id,
                'recipients' => [$messagePart->author],
            ], true);

            $this->discord->private_channels->pushItem($channel);
        }

        if ($this->discord->options['storeMessages']) {
            if ($channel = $messagePart->channel) {
                $channel->messages->pushItem($messagePart);
            }
        }

        if (isset($data->author) && ! isset($data->webhook_id)) {
            $this->cacheUser($data->author);
        }

        if (isset($data->interaction->user)) {
            $this->cacheUser($data->interaction->user);
        }

        $deferred->resolve($messagePart);
    }
}

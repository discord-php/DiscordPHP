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

/**
 * @see https://discord.com/developers/docs/topics/gateway#message-delete
 */
class MessageDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $messagePart = null;

        if (! isset($data->guild_id)) {
            if ($channel = $this->discord->private_channels->get('id', $data->channel_id)) {
                $messagePart = $channel->messages->pull($data->id);
            }
        } else {
            if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
                if ($channel = $guild->channels->get('id', $data->channel_id)) {
                    $messagePart = $channel->messages->pull($data->id);
                }
            }
        }

        $deferred->resolve(is_null($messagePart) ? $data : $messagePart);
    }
}

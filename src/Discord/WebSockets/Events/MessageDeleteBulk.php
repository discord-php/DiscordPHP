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

use Discord\Helpers\Collection;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#message-delete-bulk
 *
 * @since 4.0.0
 */
class MessageDeleteBulk extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $resolved = new Collection();

        foreach ($data->ids as $id) {
            $event = new MessageDelete($this->discord);
            $resolved->pushItem(yield from $event->handle((object) ['id' => $id, 'channel_id' => $data->channel_id, 'guild_id' => $data->guild_id]));
        }

        return $resolved;
    }
}

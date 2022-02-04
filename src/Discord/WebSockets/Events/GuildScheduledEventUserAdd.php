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

class GuildScheduledEventUserAdd extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $scheduledEvent = $user = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $scheduledEvent = $guild->guild_scheduled_events->get('id', $data->guild_scheduled_event_id);
            $user = $this->discord->users->get('id', $data->user_id);
        }

        // TODO: Create WebSockets Event Part
        $deferred->resolve([$data, $scheduledEvent, $guild, $user]);
    }
}

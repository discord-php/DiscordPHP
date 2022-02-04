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
use Discord\Parts\Guild\ScheduledEvent;

class GuildScheduledEventDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $scheduledEvent = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($scheduledEvent = $guild->guild_scheduled_events->pull($data->id)) {
                $scheduledEvent->fill((array) $data);
                $scheduledEvent->created = false;
            }
        }

        if (! $scheduledEvent) {
            /** @var ScheduledEvent */
            $scheduledEvent = $this->factory->create(ScheduledEvent::class, $data);
        }

        if (isset($data->creator)) {
            $this->cacheUser($data->creator);
        }

        $deferred->resolve($scheduledEvent);
    }
}

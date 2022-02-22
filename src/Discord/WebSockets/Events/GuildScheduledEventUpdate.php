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

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-scheduled-event-update
 */
class GuildScheduledEventUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $scheduledEventPart = $oldScheduledEvent = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldScheduledEvent = $guild->guild_scheduled_events->get('id', $data->id)) {
                // Swap
                $scheduledEventPart = $oldScheduledEvent;
                $oldScheduledEvent = clone $oldScheduledEvent;

                $scheduledEventPart->fill((array) $data);
            }
        }

        if (! $scheduledEventPart) {
            /** @var ScheduledEvent */
            $scheduledEventPart = $this->factory->create(ScheduledEvent::class, $data, true);
            if ($guild = $scheduledEventPart->guild) {
                $guild->guild_scheduled_events->pushItem($scheduledEventPart);
            }
        }

        if (isset($data->creator)) {
            $this->cacheUser($data->creator);
        }

        $deferred->resolve([$scheduledEventPart, $oldScheduledEvent]);
    }
}

<?php

declare(strict_types=1);

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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-scheduled-event-update
 *
 * @since 7.0.0
 */
class GuildScheduledEventUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $scheduledEventPart = $oldScheduledEvent = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?ScheduledEvent */
            if ($oldScheduledEvent = yield $guild->guild_scheduled_events->cacheGet($data->id)) {
                // Swap
                $scheduledEventPart = $oldScheduledEvent;
                $oldScheduledEvent = clone $oldScheduledEvent;

                $scheduledEventPart->fill((array) $data);
            }
        }

        if ($scheduledEventPart === null) {
            /** @var ScheduledEvent */
            $scheduledEventPart = $this->factory->part(ScheduledEvent::class, (array) $data, true);
        }

        $guild?->guild_scheduled_events->set($data->id, $scheduledEventPart);

        if (isset($data->creator)) {
            $this->cacheUser($data->creator);
        }

        return [$scheduledEventPart, $oldScheduledEvent];
    }
}

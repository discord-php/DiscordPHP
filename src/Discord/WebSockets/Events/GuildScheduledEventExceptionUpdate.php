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
use Discord\Parts\Guild\ScheduledEventException;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-scheduled-event-exception-update
 *
 * @since 10.45.1
 */
class GuildScheduledEventExceptionUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $oldScheduledEventException = null;
        /** @var ScheduledEventException */
        $scheduledEventExceptionPart = $this->factory->part(ScheduledEventException::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?ScheduledEvent */
            if ($oldScheduledEvent = yield $guild->guild_scheduled_events->cacheGet($data->id)) {
                /** @var ScheduledEventException */
                if ($oldScheduledEventException = $oldScheduledEvent->guild_scheduled_event_exceptions->get('event_exception_id', $scheduledEventExceptionPart->event_exception_id)) {
                    // Swap
                    $scheduledEventExceptionPart = $oldScheduledEventException;
                    $oldScheduledEventException = clone $oldScheduledEventException;

                    $scheduledEventExceptionPart->fill((array) $data);
                    
                    $guild->guild_scheduled_event_exceptions->set($scheduledEventExceptionPart->event_exception_id, $scheduledEventExceptionPart);
                }
            }
        }

        return [$scheduledEventExceptionPart, $oldScheduledEventException];
    }
}

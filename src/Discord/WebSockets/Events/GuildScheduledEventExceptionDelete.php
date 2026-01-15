<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
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
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-scheduled-event-exception-delete
 *
 * @since 10.45.1
 */
class GuildScheduledEventExceptionDelete extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var ScheduledEventException */
        $scheduledEventExceptionPart = $this->factory->part(ScheduledEventException::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($scheduledEventExceptionPart->guild_id)) {
            /** @var ?ScheduledEvent */
            if ($scheduledEventPart = yield $guild->guild_scheduled_events->cacheGet($scheduledEventExceptionPart->event_id)) {
                $scheduledEventPart->guild_scheduled_event_exceptions->pull($scheduledEventExceptionPart->event_exception_id);
            }
        }

        return $scheduledEventExceptionPart;
    }
}

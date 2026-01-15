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
use Discord\Parts\Guild\ScheduledEventException;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-scheduled-event-exception-create
 *
 * @since 10.45.1
 */
class GuildScheduledEventExceptionCreate extends Event
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
            if ($event = $guild->guild_scheduled_events->get($scheduledEventExceptionPart->event_id)) {
                $event->guild_scheduled_event_exceptions->set($scheduledEventExceptionPart->event_exception_id, $scheduledEventExceptionPart);
            }
        }

        return $scheduledEventExceptionPart;
    }
}

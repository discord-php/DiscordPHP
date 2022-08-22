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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;

use function React\Async\coroutine;

/**
 * @link https://discord.com/developers/docs/topics/gateway#guild-scheduled-event-update
 */
class GuildScheduledEventUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $scheduledEventPart = $oldScheduledEvent = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?ScheduledEvent */
                if ($oldScheduledEvent = $guild->guild_scheduled_events[$data->id]) {
                    // Swap
                    $scheduledEventPart = $oldScheduledEvent;
                    $oldScheduledEvent = clone $oldScheduledEvent;

                    $scheduledEventPart->fill((array) $data);
                }
            }

            if ($scheduledEventPart === null) {
                /** @var ScheduledEvent */
                $scheduledEventPart = $this->factory->create(ScheduledEvent::class, $data, true);
            }

            if ($guild) {
                yield $guild->guild_scheduled_events->cache->set($data->id, $scheduledEventPart);
            }

            if (isset($data->creator)) {
                $this->cacheUser($data->creator);
            }

            return [$scheduledEventPart, $oldScheduledEvent];
        }, $data)->then([$deferred, 'resolve']);
    }
}

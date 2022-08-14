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
 * @see https://discord.com/developers/docs/topics/gateway#guild-scheduled-event-delete
 */
class GuildScheduledEventDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $scheduledEventPart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?ScheduledEvent */
                if ($scheduledEventPart = yield $guild->guild_scheduled_events->cachePull($data->id)) {
                    $scheduledEventPart->fill((array) $data);
                    $scheduledEventPart->created = false;
                }
            }

            if (isset($data->creator)) {
                $this->cacheUser($data->creator);
            }

            return $scheduledEventPart ?? $this->factory->create(ScheduledEvent::class, $data);
        }, $data)->then([$deferred, 'resolve']);
    }
}

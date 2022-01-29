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
        /** @var ScheduledEvent */
        $scheduled_event = $this->factory->create(ScheduledEvent::class, $data);

        if ($guild = $scheduled_event->guild) {
            $guild->guild_scheduled_events->pull($scheduled_event->id);
        }

        if (isset($data->creator)) {
            $this->cacheUser($data->creator);
        }

        $deferred->resolve($scheduled_event);
    }
}

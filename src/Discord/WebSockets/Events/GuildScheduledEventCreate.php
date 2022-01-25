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
use Discord\Parts\User\User;

class GuildScheduledEventCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var ScheduledEvent */
        $scheduled_event = $this->factory->create(ScheduledEvent::class, $data, true);

        if ($guild = $this->discord->guilds->get('id', $scheduled_event->guild_id)) {
            $guild->guild_scheduled_events->push($scheduled_event);
        }

        // User caching
        if (isset($data->creator)) {
            if ($user = $this->discord->users->get('id', $data->creator->id)) {
                $user->fill((array) $data->creator);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->creator, true));
            }
        }

        $deferred->resolve($scheduled_event);
    }
}

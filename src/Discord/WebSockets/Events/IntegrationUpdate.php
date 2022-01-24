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
use Discord\Parts\Guild\Integration;

class IntegrationUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $integration = $this->factory->part(Integration::class, (array) $data, true);

        /** @var Guild */
        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $guild->integrations->pushItem($integration);
        }

        // User caching
        if (! isset($data->user)) {
            if ($user = $this->discord->users->get('id', $data->user->id)) {
                $user->fill((array) $data->user);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->user, true));
            }
        }

        $deferred->resolve($integration);
    }
}

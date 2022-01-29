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

class IntegrationCreate extends Event
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

        if (isset($data->user)) {
            $this->cacheUser($data->user);
        }

        $deferred->resolve($integration);
    }
}

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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;

class WebhooksUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var Guild */
        if ($guild = $this->discord->guilds->offsetGet($data->guild_id)) {
            /** @var Channel */
            $channel = $guild->channels->offsetGet($data->channel_id);
        }

        $deferred->resolve([$guild, $channel]);
    }
}

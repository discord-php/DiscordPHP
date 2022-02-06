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

/**
 * @see https://discord.com/developers/docs/topics/gateway#integration-delete
 */
class IntegrationDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $oldIntegration = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldIntegration = $guild->integrations->pull($data->id)) {
                $oldIntegration->created = false;
            }
        }

        $deferred->resolve($oldIntegration);
    }
}

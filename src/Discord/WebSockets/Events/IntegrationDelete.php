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
use Discord\Parts\Guild\Integration;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#integration-delete
 *
 * @since 7.0.0
 */
class IntegrationDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldIntegration = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Integration */
            if ($oldIntegration = yield $guild->integrations->cachePull($data->id)) {
                $oldIntegration->created = false;
            }
        }

        return $oldIntegration ?? $data;
    }
}

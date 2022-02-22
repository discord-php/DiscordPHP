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

/**
 * @see https://discord.com/developers/docs/topics/gateway#integration-update
 */
class IntegrationUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $integrationPart = $oldIntegration = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldIntegration = $guild->integrations->get('id', $data->id)) {
                // Swap
                $integrationPart = $oldIntegration;
                $oldIntegration = clone $oldIntegration;

                $integrationPart->fill((array) $data);
            }
        }

        if (! $integrationPart) {
            /** @var Integration */
            $integrationPart = $this->factory->create(Integration::class, $data, true);
            if ($guild = $integrationPart->guild) {
                $guild->integrations->pushItem($integrationPart);
            }
        }

        if (isset($data->user)) {
            $this->cacheUser($data->user);
        }

        $deferred->resolve([$integrationPart, $oldIntegration]);
    }
}

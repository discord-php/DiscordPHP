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

use Discord\Parts\Interaction\Entitlement;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#entitlement-update
 *
 * @since 10.0.0
 */
class EntitlementUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldEntitlement = null;

        /** @var Entitlement */
        $entitlementPart = $this->factory->part(Entitlement::class, (array) $data, true);

        if ($oldEntitlement = yield $this->discord->application->entitlements->cacheGet($data->id)) {
            // Swap
            $entitlementPart = $oldEntitlement;
            $oldEntitlement = clone $oldEntitlement;

            $entitlePart->fill((array) $data);

            if ($entitlementPart->guild_id && $part = $this->discord->guilds->get('id', $entitlementPart->guild_id)->entitlements->get('id', $data->id)) {
                $part->fill((array) $data);
            }

            if ($entitlementPart->user_id && $part = $this->discord->users->get('id', $entitlementPart->user_id)->entitlements->get('id', $data->id)) {
                $part->fill((array) $data);
            }
        }

        return [$entitlementPart, $oldEntitlement];
    }
}

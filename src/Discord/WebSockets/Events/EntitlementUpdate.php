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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Entitlement;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-entitlementboard-entitlement-update
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
        $newEntitlementPart = $oldEntitlementPart = null;

        if (isset($data->guild_id)) {
            /** @var Guild|null */
            $guild = yield $this->discord->guilds->cacheGet($data->guild_id);
            if ($guild instanceof Guild) {
                /** @var ?Entitlement */
                $oldEntitlementPart = yield $guild->entitlements->cacheGet($data->id);
                if ($oldEntitlementPart instanceof Entitlement) {
                    $newEntitlementPart = clone $oldEntitlementPart;
                    $newEntitlementPart->fill((array) $data);
                }
                $guild->entitlements->set($data->id, $newEntitlementPart ?? $this->factory->part(Entitlement::class, (array) $data, true));
            }
        } else {
            /** @var ?Entitlement */
            $oldEntitlementPart = yield $this->discord->entitlements->cacheGet($data->id);
            if ($oldEntitlementPart instanceof Entitlement) {
                $newEntitlementPart = clone $oldEntitlementPart;
                $newEntitlementPart->fill((array) $data);
            }
        }

        /** @var Entitlement */
        $newEntitlementPart = $newEntitlementPart ?? $this->factory->part(Entitlement::class, (array) $data, true);

        $this->discord->entitlements->set($data->id, $newEntitlementPart);

        return [$newEntitlementPart, $oldEntitlementPart];
    }
}









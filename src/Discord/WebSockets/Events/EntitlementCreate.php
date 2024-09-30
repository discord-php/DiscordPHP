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
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sound-create
 *
 * @since 10.0.0
 */
class EntitlementCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Entitlement */
        $entitlementPart = $this->factory->part(Entitlement::class, (array) $data, true);

        if (isset($data->guild_id)) {
            /** @var Guild|null */
            $guild = yield $this->discord->guilds->cacheGet($data->guild_id);
            if ($guild instanceof Guild) {
                $guild->entitlements->set($data->id, $entitlementPart);
                return $entitlementPart;
            }
        }

        $this->discord->entitlements->set($data->id, $entitlementPart);

        return $entitlementPart;
    }
}

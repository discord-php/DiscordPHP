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
use Discord\Parts\Interaction\Entitlement;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#entitlement-create
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

        $this->discord->application->entitlements->set($data->id, $entitlementPart);

        if (isset($data->guild_id) && $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            $guild->entitlements->set($data->id, $entitlementPart);
        }

        if (isset($data->user_id) && $user = yield $this->discord->users->cacheGet($data->user_id)) {
            $user->entitlements->set($data->id, $entitlementPart);
        }

        return $entitlementPart;
    }
}

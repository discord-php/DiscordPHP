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
 * @link https://discord.com/developers/docs/topics/gateway-events#entitlement-delete
 *
 * @since 10.0.0
 */
class EntitlementDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $entitlementPart = null;

        /** @var ?\Discord\Parts\Interaction\Entitlement */
        if ($entitlementPart = yield $this->discord->application->entitlements->pull($data->id)) {
            /** @var User */
            if ($entitlementPart->user_id && $user = $this->discord->users->get('id', $entitlementPart->user_id)) {
                $user->entitlements->unset($data->id);
            }

            /** @var Guild */
            if ($entitlementPart->guild_id && $guild = $this->discord->guilds->get('id', $entitlementPart->guild_id)) {
                $guild->entitlements->unset($data->id);
            }

            $entitlementPart->fill((array) $data);
            $entitlementPart->created = false;                
        }

        return $entitlementPart ?? $this->factory->part(Entitlement::class, (array) $data);
    }
}

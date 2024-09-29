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

use Discord\Parts\Entitlement;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sound-delete
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
        /** @var ?Entitlement */
        $entitlementPart = yield $this->discord->entitlements->cachePull($data->id);

        return $entitlementPart ?? $this->factory->part(Entitlement::class, (array) $data);
    }
}

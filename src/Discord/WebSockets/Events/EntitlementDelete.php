<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Monetization\Entitlement;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#entitlement-delete
 *
 * @since 10.15.0
 */
class EntitlementDelete extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        if ($entitlementPart = yield $this->discord->application->entitlements->cachePull($data->id)) {
            $entitlementPart->fill((array) $data);
            $entitlementPart->created = false;
        }

        return $entitlementPart ?? $this->factory->part(Entitlement::class, (array) $data);
    }
}

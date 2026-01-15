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

use Discord\WebSockets\Event;
use Discord\Parts\Monetization\Entitlement;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#entitlement-create
 *
 * @since 10.15.0
 */
class EntitlementCreate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var Entitlement */
        $entitlementPart = $this->factory->part(Entitlement::class, (array) $data, true);

        $this->discord->application->entitlements->set($data->id, $entitlementPart);

        return $entitlementPart;
    }
}

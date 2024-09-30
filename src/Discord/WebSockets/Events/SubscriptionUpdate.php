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

use Discord\Parts\SKUs\Subscription;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-Subscriptionboard-Subscription-update
 *
 * @since 10.0.0
 */
class SubscriptionUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $newSubscriptionPart = $oldSubscriptionPart = null;

        /** @var ?Subscription */
        $oldSubscriptionPart = yield $this->discord->Subscriptions->cacheGet($data->id);
        if ($oldSubscriptionPart instanceof Subscription) {
            $newSubscriptionPart = clone $oldSubscriptionPart;
            $newSubscriptionPart->fill((array) $data);
        }

        /** @var Subscription */
        $newSubscriptionPart = $newSubscriptionPart ?? $this->factory->part(Subscription::class, (array) $data, true);

        $this->discord->Subscriptions->set($data->id, $newSubscriptionPart);

        return [$newSubscriptionPart, $oldSubscriptionPart];
    }
}

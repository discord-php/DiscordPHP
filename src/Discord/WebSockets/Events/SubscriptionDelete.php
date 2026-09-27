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

use Discord\Parts\Monetization\SKU;
use Discord\Parts\Monetization\Subscription;
use Discord\WebSockets\Event;

/**
 * A user's subscription was deleted. Discord does not usually delete subscriptions; one that ends is
 * updated with its new status instead.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#subscription-delete
 *
 * @since 10.60.0
 */
class SubscriptionDelete extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var Subscription */
        $subscriptionPart = $this->factory->part(Subscription::class, (array) $data, true);

        if ($subscriptionPart->sku_ids) {
            foreach ($subscriptionPart->sku_ids as $skuId) {
                /** @var ?SKU $sku */
                if ($sku = $this->discord->application->skus->get('id', $skuId)) {
                    if ($old = yield $sku->subscriptions->cachePull($data->id)) {
                        $subscriptionPart = $old;
                        $subscriptionPart->fill((array) $data);
                    }
                }
            }
        }

        $subscriptionPart->created = false;

        return $subscriptionPart;
    }
}

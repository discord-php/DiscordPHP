<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Monetization\SKU;
use Discord\Parts\Monetization\Subscription;
use Discord\WebSockets\Event;

/**
 * @link discord.com/developers/docs/monetization/implementing-app-subscriptions
 *
 * @since 10.42.0
 */
class SubscriptionCreate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var Subscription $subscriptionPart */
        $subscriptionPart = $this->factory->part(Subscription::class, (array) $data, true);

        if ($subscriptionPart->sku_ids) {
            foreach ($subscriptionPart->sku_ids as $skuId) {
                /** @var SKU $sku */
                $sku = $this->discord->application->skus->get('id', $skuId) ?? $this->factory->part(SKU::class, ['id' => $skuId, 'application_id' => $this->discord->application->id], true);

                $sku->subscriptions->set($subscriptionPart->id, $subscriptionPart);
            }
        }

        return $subscriptionPart;
    }
}

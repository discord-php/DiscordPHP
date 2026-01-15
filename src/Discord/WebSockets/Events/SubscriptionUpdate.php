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
 * @link discord.com/developers/docs/monetization/implementing-app-subscriptions
 *
 * @since 10.42.0
 */
class SubscriptionUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $oldSubscription = null;

        /** @var Subscription */
        $subscriptionPart = $this->factory->part(Subscription::class, (array) $data, true);

        if ($subscriptionPart->sku_ids) {
            foreach ($subscriptionPart->sku_ids as $skuId) {
                /** @var SKU $sku */
                $sku = $this->discord->application->skus->get('id', $skuId) ?? $sku = $this->factory->part(SKU::class, ['id' => $skuId, 'application_id' => $this->discord->application->id], true);

                if ($old = yield $sku->subscriptions->cacheGet($data->id)) {
                    $subscriptionPart = $old;
                    $oldSubscription = clone $old;

                    $subscriptionPart->fill((array) $data);

                    $sku->subscriptions->set($data->id, $subscriptionPart);
                }
            }
        }

        return [$subscriptionPart, $oldSubscription];
    }
}

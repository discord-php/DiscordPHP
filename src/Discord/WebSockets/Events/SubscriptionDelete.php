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
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sound-delete
 *
 * @since 10.0.0
 */
class SubscriptionDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var ?Subscription */
        $subscriptionPart = yield $this->discord->subscriptions->cachePull($data->id);

        return $subscriptionPart ?? $this->factory->part(Subscription::class, (array) $data);
    }
}

<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\SKUs;

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\SKUs\Subscription;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\resolve;

/**
 * Contains subscriptions of an application.
 *
 * @see Subscription
 * @see \Discord\Parts\User\Client
 *
 * @since 10.0.0
 *
 * @method Subscription|null get(string $discrim, $key)
 * @method Subscription|null pull(string|int $key, $default = null)
 * @method Subscription|null first()
 * @method Subscription|null last()
 * @method Subscription|null find(callable $callback)
 */
class SubscriptionRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::SKU_SUBSCRIPTIONS,
        'get' => Endpoint::SKU_SUBSCRIPTION,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Subscription::class;
}

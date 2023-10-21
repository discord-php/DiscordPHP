<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Interaction;

use Discord\Http\Endpoint;
use Discord\Parts\Interaction\Entitlement;
use Discord\Repository\AbstractRepository;

/**
 * Guild/User entitlements.
 *
 * @see \Discord\Parts\Guild\Guild
 * @see \Discord\Parts\User\User
 *
 * @since 10.0.0
 *
 * @method Entitlement|null get(string $discrim, $key)
 * @method Entitlement|null pull(string|int $key, $default = null)
 * @method Entitlement|null first()
 * @method Entitlement|null last()
 * @method Entitlement|null find(callable $callback)
 */
class EntitlementsRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::APPLICATION_ENTITLEMENTS,
        'create' => Endpoint::APPLICATION_TEST_ENTITLEMENTS,
        'delete' => Endpoint::APPLICATION_TEST_ENTITLEMENT,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Entitlement::class;
}

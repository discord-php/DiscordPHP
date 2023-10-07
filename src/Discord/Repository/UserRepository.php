<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Http\Endpoint;
use Discord\Parts\User\User;

/**
 * Contains users that the client shares guilds with.
 *
 * @see User
 *
 * @since 4.0.0
 *
 * @method User|null get(string $discrim, $key)
 * @method User|null pull(string|int $key, $default = null)
 * @method User|null first()
 * @method User|null last()
 * @method User|null find(callable $callback)
 */
class UserRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'get' => Endpoint::USER,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = User::class;
}

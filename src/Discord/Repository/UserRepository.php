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
 * Contains users that the user shares guilds with.
 *
 * @see \Discord\Parts\User\User
 *
 * @method User|null get(string $discrim, $key)  Gets an item from the collection.
 * @method User|null first()                     Returns the first element of the collection.
 * @method User|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method User|null find(callable $callback)    Runs a filter callback over the repository.
 */
class UserRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'get' => Endpoint::USER,
    ];

    /**
     * @inheritdoc
     */
    protected $class = User::class;
}

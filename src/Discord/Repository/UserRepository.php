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

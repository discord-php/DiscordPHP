<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Parts\User\User;

/**
 * Contains users that the user shares guilds with.
 *
 * @see Discord\Parts\User\User
 */
class UserRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'get' => 'users/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = User::class;
}

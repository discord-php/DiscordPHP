<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Role;
use Discord\Repository\AbstractRepository;

/**
 * Contains roles that belong to the guild.
 *
 * @see \Discord\Parts\Guild\Role
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Role|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Role|null first()                     Returns the first element of the collection.
 * @method Role|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Role|null find(callable $callback)    Runs a filter callback over the repository.
 */
class RoleRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_ROLES,
        'create' => Endpoint::GUILD_ROLES,
        'update' => Endpoint::GUILD_ROLE,
        'delete' => Endpoint::GUILD_ROLE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Role::class;
}

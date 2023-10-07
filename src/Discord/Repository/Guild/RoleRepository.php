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
 * Contains roles of a guild.
 *
 * @since 4.0.0
 *
 * @see Role
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Role|null get(string $discrim, $key)
 * @method Role|null pull(string|int $key, $default = null)
 * @method Role|null first()
 * @method Role|null last()
 * @method Role|null find(callable $callback)
 */
class RoleRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_ROLES,
        'create' => Endpoint::GUILD_ROLES,
        'update' => Endpoint::GUILD_ROLE,
        'delete' => Endpoint::GUILD_ROLE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Role::class;
}

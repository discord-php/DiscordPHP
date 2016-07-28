<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Parts\Guild\Role;
use Discord\Repository\AbstractRepository;

/**
 * Contains roles that belong to the guild.
 *
 * @see Discord\Parts\Guild\Role
 * @see Discord\Parts\Guild\Guild
 */
class RoleRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all'    => 'guilds/:guild_id/roles',
        'get'    => 'guilds/:guild_id/roles/:id',
        'create' => 'guilds/:guild_id/roles',
        'update' => 'guilds/:guild_id/roles/:id',
        'delete' => 'guilds/:guild_id/roles/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Role::class;
}

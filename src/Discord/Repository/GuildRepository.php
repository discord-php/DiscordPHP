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

use Discord\Parts\Guild\Guild;

/**
 * Contains guilds that the user is in.
 *
 * @see Discord\Parts\Guild\Guild
 */
class GuildRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all'    => 'users/@me/guilds',
        'get'    => 'guilds/:id',
        'create' => 'guilds',
        'update' => 'guilds/:id',
        'delete' => 'guilds/:id',
        'leave'  => 'users/@me/guilds/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Guild::class;
}

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

use Discord\Parts\Guild\Ban;
use Discord\Repository\AbstractRepository;

/**
 * Contains bans on users.
 *
 * @see Discord\Parts\Guild\Ban
 * @see Discord\Parts\Guild\Guild
 */
class BanRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $discrim = 'user_id';

    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all'    => 'guilds/:guild_id/bans',
        'create' => 'guilds/:guild_id/bans/:user_id',
        'delete' => 'guilds/:guild_id/bans/:user_id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Ban::class;
}

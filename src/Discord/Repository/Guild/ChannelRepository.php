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

use Discord\Parts\Channel\Channel;
use Discord\Repository\AbstractRepository;

class ChannelRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'guilds/:guild_id/channels',
        'get' => 'channels/:id',
        'create' => 'guilds/:guild_id/channels',
        'update' => 'channels/:id',
        'delete' => 'guilds/:guild_id/channels',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Channel::class;
}

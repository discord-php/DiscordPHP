<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Invite;
use Discord\Repository\AbstractRepository;

/**
 * Contains invites to guilds.
 *
 * @see \Discord\Parts\Guild\Invite
 * @see \Discord\Parts\Guild\Guild
 */
class InviteRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_INVITES,
        'get' => Endpoint::INVITE,
        'create' => Endpoint::GUILD_INVITES,
        'delete' => Endpoint::INVITE,
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Invite::class;
}

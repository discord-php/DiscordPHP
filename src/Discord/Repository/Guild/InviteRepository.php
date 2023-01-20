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
use Discord\Parts\Channel\Invite;
use Discord\Repository\AbstractRepository;

/**
 * Contains invites to a guild.
 *
 * @since 4.0.0
 *
 * @see Invite
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Invite|null get(string $discrim, $key)
 * @method Invite|null pull(string|int $key, $default = null)
 * @method Invite|null first()
 * @method Invite|null last()
 * @method Invite|null find()
 */
class InviteRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_INVITES,
        'get' => Endpoint::INVITE,
        'delete' => Endpoint::INVITE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Invite::class;
}

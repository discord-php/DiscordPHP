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
 * Contains invites to guilds.
 *
 * @see \Discord\Parts\Guild\Invite
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Invite|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Invite|null first()                     Returns the first element of the collection.
 * @method Invite|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Invite|null find(callable $callback)    Runs a filter callback over the repository.
 */
class InviteRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_INVITES,
        'get' => Endpoint::INVITE,
        'delete' => Endpoint::INVITE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Invite::class;
}

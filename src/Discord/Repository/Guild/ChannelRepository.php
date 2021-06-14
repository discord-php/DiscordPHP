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
use Discord\Parts\Channel\Channel;
use Discord\Repository\AbstractRepository;

/**
 * Contains channels that belong to guilds.
 *
 * @see \Discord\Parts\Channel\Channel
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Channel|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Channel|null first()                     Returns the first element of the collection.
 * @method Channel|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Channel|null find(callable $callback)    Runs a filter callback over the repository.
 */
class ChannelRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_CHANNELS,
        'get' => Endpoint::CHANNEL,
        'create' => Endpoint::GUILD_CHANNELS,
        'update' => Endpoint::CHANNEL,
        'delete' => Endpoint::CHANNEL,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Channel::class;
}

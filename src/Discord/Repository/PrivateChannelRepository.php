<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Parts\Channel\Channel;
use Discord\Http\Endpoint;

/**
 * Contains private channels and groups that the user has access to.
 *
 * @see \Discord\Parts\Channel\Channel
 *
 * @method Channel|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Channel|null first()                     Returns the first element of the collection.
 * @method Channel|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Channel|null find(callable $callback)    Runs a filter callback over the repository.
 */
class PrivateChannelRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Channel::class;
}

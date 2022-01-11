<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Interaction;

use Discord\Http\Endpoint;
use Discord\Parts\Interactions\Command\Command;
use Discord\Repository\AbstractRepository;

/**
 * Contains application global commands.
 *
 * @see \Discord\Parts\Interactions\Command\Command
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Command|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Command|null first()                     Returns the first element of the collection.
 * @method Command|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Command|null find(callable $callback)    Runs a filter callback over the repository.
 */
class GlobalCommandRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GLOBAL_APPLICATION_COMMANDS,
        'get' => Endpoint::GLOBAL_APPLICATION_COMMAND,
        'create' => Endpoint::GLOBAL_APPLICATION_COMMANDS,
        'update' => Endpoint::GLOBAL_APPLICATION_COMMAND,
        'delete' => Endpoint::GLOBAL_APPLICATION_COMMAND,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Command::class;
}

<?php

declare(strict_types=1);

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
 * @see Command
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method Command|null get(string $discrim, $key)
 * @method Command|null pull(string|int $key, $default = null)
 * @method Command|null first()
 * @method Command|null last()
 * @method Command|null find(callable $callback)
 */
class GlobalCommandRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GLOBAL_APPLICATION_COMMANDS,
        'get' => Endpoint::GLOBAL_APPLICATION_COMMAND,
        'create' => Endpoint::GLOBAL_APPLICATION_COMMANDS,
        'update' => Endpoint::GLOBAL_APPLICATION_COMMAND,
        'delete' => Endpoint::GLOBAL_APPLICATION_COMMAND,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Command::class;
}

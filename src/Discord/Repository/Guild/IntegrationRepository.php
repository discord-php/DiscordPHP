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
use Discord\Parts\Guild\Integration;
use Discord\Repository\AbstractRepository;

/**
 * Contains integrations on a guild.
 *
 * @see Integration
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method Integration|null get(string $discrim, $key)
 * @method Integration|null pull(string|int $key, $default = null)
 * @method Integration|null first()
 * @method Integration|null last()
 * @method Integration|null find(callable $callback)
 */
class IntegrationRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_INTEGRATIONS,
        'delete' => Endpoint::GUILD_INTEGRATION,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Integration::class;
}

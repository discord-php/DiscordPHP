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
 * Contains integrations to guilds.
 *
 * @see \Discord\Parts\Guild\Integration
 * @see \Discord\Parts\Guild\Guild
 */
class IntegrationRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_INTEGRATIONS,
        'delete' => Endpoint::GUILD_INTEGRATION,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Integration::class;
}

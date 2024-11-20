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

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\CommandPermissions;
use Discord\Repository\AbstractRepository;

/**
 * Contains permission overwrites for application guild commands.
 *
 * @see CommandPermissions
 * @see \Discord\Parts\Interactions\Command\Command
 * @see \Discord\Parts\Interactions\Command\Permission
 *
 * @since 10.0.0 Refactored from OverwriteRepository to CommandPermissionsRepository
 * @since 7.0.0
 *
 * @method CommandPermissions|null get(string $discrim, $key)
 * @method CommandPermissions|null pull(string|int $key, $default = null)
 * @method CommandPermissions|null first()
 * @method CommandPermissions|null last()
 * @method CommandPermissions|null find(callable $callback)
 */
class CommandPermissionsRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_APPLICATION_COMMANDS_PERMISSIONS,
        'get' => Endpoint::GUILD_APPLICATION_COMMAND_PERMISSIONS,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = CommandPermissions::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id; // For the bot's Application Guild Commands

        parent::__construct($discord, $vars);
    }
}

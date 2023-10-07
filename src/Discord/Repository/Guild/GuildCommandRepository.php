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
use Discord\Parts\Interactions\Command\Command;
use Discord\Repository\AbstractRepository;

/**
 * Contains application guild commands.
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
class GuildCommandRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'get' => Endpoint::GUILD_APPLICATION_COMMAND,
        'create' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'update' => Endpoint::GUILD_APPLICATION_COMMAND,
        'delete' => Endpoint::GUILD_APPLICATION_COMMAND,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Command::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id; // For the bot's Application Guild Commands

        parent::__construct($discord, $vars);
    }
}

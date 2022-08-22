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
use Discord\Parts\Interactions\Command\Command;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;

/**
 * Contains application guild commands.
 *
 * @see Command
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Command|null get(string $discrim, $key)
 * @method Command|null pull(string|int $key, $default = null)
 * @method Command|null first()
 * @method Command|null last()
 * @method Command|null find()
 */
class GuildCommandRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'get' => Endpoint::GUILD_APPLICATION_COMMAND,
        'create' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'update' => Endpoint::GUILD_APPLICATION_COMMAND,
        'delete' => Endpoint::GUILD_APPLICATION_COMMAND,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Command::class;

    /**
     * @inheritdoc
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id; // For the bot's Application Guild Commands

        parent::__construct($discord, $vars);
    }

    /**
     * Sets all guild commands permission overwrites.
     *
     * @link https://discord.com/developers/docs/interactions/application-commands#batch-edit-application-command-permissions
     *
     * @param CommandPermissions $overwrite An overwrite object.
     *
     * @deprecated 7.1.0 Removed on Permissions v2.
     *
     * @return ExtendedPromiseInterface
     */
    public function setOverwrite(CommandPermissions $overwrite): ExtendedPromiseInterface
    {
        return reject(new \RuntimeException('Bots can no longer set guild command permissions'));
    }
}

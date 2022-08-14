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
use Discord\Parts\Guild\CommandPermissions;
use Discord\Parts\Interactions\Command\Command;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;

/**
 * Contains application guild commands.
 *
 * @see \Discord\Parts\Interactions\Command\Command
 * @see \Discord\Parts\Guild\Guild
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
     * Sets all guild commands permission overwrites.
     *
     * @see https://discord.com/developers/docs/interactions/application-commands#batch-edit-application-command-permissions
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

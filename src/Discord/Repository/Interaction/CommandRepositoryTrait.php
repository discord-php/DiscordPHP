<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Interaction;

use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Command;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Replaces all of an application's commands in one request. Shared by the global and guild command
 * repositories.
 *
 * @since 10.60.0
 */
trait CommandRepositoryTrait
{
    /**
     * Overwrites the application's commands with the ones given, in a single request.
     *
     * Commands missing from the list are deleted, those whose name and type match an existing command
     * update it, and the rest are created. Commands that do not change do not count toward the daily
     * limit on creating them. Afterwards the repository holds exactly the commands Discord returns.
     *
     * @link https://docs.discord.com/developers/interactions/application-commands#bulk-overwrite-global-application-commands
     * @link https://docs.discord.com/developers/interactions/application-commands#bulk-overwrite-guild-application-commands
     *
     * @param array<CommandBuilder|Command|array> $commands Every command the application should have here.
     *
     * @return PromiseInterface<static>
     *
     * @since 10.60.0
     */
    public function bulkOverwrite(array $commands): PromiseInterface
    {
        $payload = [];

        foreach ($commands as $command) {
            $payload[] = match (true) {
                $command instanceof CommandBuilder => $command->jsonSerialize(),
                $command instanceof Command => $command->getCreatableAttributes(),
                is_array($command) => $command,
                default => null,
            };
        }

        if (in_array(null, $payload, true)) {
            return reject(new \InvalidArgumentException('Each command must be a CommandBuilder, a Command or an array.'));
        }

        return $this->putCommands($payload)->then(function ($response) {
            $this->forgetCachedItems();

            return $this->cacheFreshen($response);
        });
    }

    /**
     * Sends the complete list of commands, to the route for global commands or for a guild's.
     *
     * Each repository names its own route here, so the request is visible where its endpoint is.
     *
     * @param array<array> $commands The commands, ready to send.
     *
     * @return PromiseInterface<array> The commands as Discord now has them.
     */
    abstract protected function putCommands(array $commands): PromiseInterface;
}

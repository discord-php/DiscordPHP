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

namespace Discord\MessageCommandClient;

/**
 * Small DTO returned by MessageCommandClient::buildCommand().
 */
final class BuiltCommand
{
    /**
     * The Command instance built by the client.
     *
     * @var Command
     */
    public Command $command;

    /**
     * Resolved options for the command.
     *
     * @var array<string,mixed>
     */
    public array $options;

    public function __construct(Command $command, array $options)
    {
        $this->command = $command;
        $this->options = $options;
    }
}

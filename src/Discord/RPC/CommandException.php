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

namespace Discord\RPC;

/** A command ERROR response. Unknown numeric codes are retained without logging server data. */
final class CommandException extends \RuntimeException
{
    public function __construct(private string $command, int $rpcCode)
    {
        parent::__construct('RPC command '.$command.' failed (code '.$rpcCode.').', $rpcCode);
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}

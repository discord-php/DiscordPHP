<?php

/*
 * This file was a part of the DiscordPHP-Slash project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Discord\Enums;

/**
 * @link https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-option-type
 * @author David Cole <david.cole1340@gmail.com>
 */
final class ApplicationCommandOptionType
{
    public const SUB_COMMAND = 1;
    public const SUB_COMMAND_GROUP = 2;
    public const STRING = 3;
    public const INTEGER = 4;
    public const BOOLEAN = 5;
    public const USER = 6;
    public const CHANNEL = 7;
    public const ROLE = 8;
    public const MENTIONABLE = 9;
    public const NUMBER = 10;
}

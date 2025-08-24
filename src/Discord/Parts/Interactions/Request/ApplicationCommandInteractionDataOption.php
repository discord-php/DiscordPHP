<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Request;

use Discord\Parts\Interactions\Command\Command;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-application-command-interaction-data-option-structure
 *
 * @since 10.19.0
 *
 * @property string                      $name    Name of the parameter.
 * @property int                         $type    Value of application command option type.
 * @property ?string|int|float|bool|null $value   Value of the option resulting from user input.
 * @property ?array|null                 $options Present if this option is a group or subcommand.
 * @property ?bool|null                  $focused True if this option is the currently focused option for autocomplete.
 */
class ApplicationCommandInteractionDataOption extends InteractionData
{
    protected $fillable = [
        'name',
        'type',
        'value',
        'options',
        'focused',
    ];
}

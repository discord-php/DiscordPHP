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

namespace Discord\Parts\Channel\Message;

use Discord\Helpers\ExCollectionInterface;

/**
 * A User Select is an interactive component that allows users to select one or more users in a message. Options are automatically populated based on the server's available users.
 *
 * User Selects can be configured for both single-select and multi-select behavior. When a user finishes making their choice(s) your app receives an interaction.
 *
 * User Selects must be placed inside an Action Row and are only available in messages. An Action Row can contain only one select menu and cannot contain buttons if it has a select menu.
 *
 * @link https://discord.com/developers/docs/components/reference#user-select
 *
 * @since 10.11.0
 *
 * @property int                                       $type           5 for user select.
 * @property string|null                               $id             Optional identifier for component.
 * @property string                                    $custom_id      ID for the select menu; max 100 characters
 * @property string|null                               $placeholder    Placeholder text if nothing is selected; max 150 characters.
 * @property ExCollectionInterface|DefaultValue[]|null $default_values List of default values for auto-populated select menu components; number of default values must be in the range defined by min_values and max_values
 * @property int|null                                  $min_values     Minimum number of items that must be chosen (defaults to 1); min 0, max 25.
 * @property int|null                                  $max_values     Maximum number of items that can be chosen (defaults to 1); max 25.
 * @property bool|null                                 $disabled       Whether select menu is disabled (defaults to false).
 */
class UserSelect extends SelectMenu
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'custom_id',
        'placeholder',
        'default_values',
        'min_values',
        'max_values',
        'disabled',
    ];
}

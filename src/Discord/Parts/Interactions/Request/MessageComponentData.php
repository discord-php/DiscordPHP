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

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message\Component;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Part;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-message-component-data-structure
 *
 * @since 10.19.0
 *
 * @property string        $custom_id      Custom ID of the component.
 * @property int           $component_type Type of the component.
 * @property string[]      $values         Values the user selected in a select menu component.
 * @property Resolved|null $resolved       Resolved entities from selected options.
 */
class MessageComponentData extends InteractionData
{
    protected $fillable = [
        'custom_id',
        'component_type',
        'values',
        'resolved',
    ];
}

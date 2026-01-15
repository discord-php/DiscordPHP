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

namespace Discord\Parts\Interactions\Request;

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

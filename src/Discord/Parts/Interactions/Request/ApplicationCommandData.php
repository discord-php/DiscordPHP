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

use Discord\Helpers\ExCollectionInterface;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-application-command-data-structure
 *
 * @since 10.19.0
 *
 * @property string                                  $id        ID of the invoked command.
 * @property string                                  $name      Name of the invoked command.
 * @property int                                     $type      Type of the invoked command.
 * @property ?Resolved|null                          $resolved  Converted users, roles, channels, attachments.
 * @property ?ExCollectionInterface<Option>|Option[] $options   Params and values from the user.
 * @property ?string|null                            $guild_id  ID of the guild the command is registered to.
 * @property ?string|null                            $target_id ID of the user or message targeted by a user or message command.
 */
class ApplicationCommandData extends InteractionData
{
    protected $fillable = [
        'id',
        'name',
        'type',
        'resolved',
        'options',
        'guild_id',
        'target_id',
    ];
}

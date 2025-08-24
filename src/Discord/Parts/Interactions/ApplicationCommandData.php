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

namespace Discord\Parts\Interactions;

use Discord\Parts\Part;

/**
 * Represents the data for an application command interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-data
 *
 * @property      string            $id        ID of the invoked command.
 * @property      string            $name      name of the invoked command.
 * @property      int               $type      type of the invoked command.
 * @property      array             $resolved  Converted users + roles + channels + attachments.
 * @property      array             $options   Params + values from the user.
 * @property      string            $guild_id  ID of the guild the command is registered to.
 * @property      string            $target_id ID of the user or message targeted by a user or message command.
 * @property-read Guild|null        $guild     The guild the command is registered to.
 * @property-read User|Message|null $target    The user or message targeted by the command.
 */
class ApplicationCommandData extends Part
{
    /**
     * Type of the interaction.
     *
     * @var int
     */
    protected $type = Interaction::TYPE_APPLICATION_COMMAND;

    /**
     * @inheritDoc
     */
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

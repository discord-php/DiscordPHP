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

use Discord\Parts\Part;

/**
 * List of default values for auto-populated select menu components; number of default values must be in the range defined by min_values and max_values
 *
 * @link https://discord.com/developers/docs/components/reference#user-select-select-default-value-structure
 *
 * @since 10.11.0
 *
 * @property string $id   ID of a user, role, or channel.
 * @property string $type Type of value that id represents. Either "user", "role", or "channel"
 */
class DefaultValue extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
    ];
}

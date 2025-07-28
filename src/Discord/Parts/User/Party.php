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

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * Information for the current party of the player.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#activity-object-activity-party
 *
 * @since 10.19.0
 *
 * @property string       $id   ID of the party
 * @property object|array $size Array of two integers (current_size, max_size). Used to show the party's current and maximum size.
 */
class Party extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'size',
    ];
}

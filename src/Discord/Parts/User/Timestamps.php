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
 * Unix timestamps for start and/or end of the game.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#activity-object-activity-timestamps
 *
 * @since 10.24.0
 *
 * @property int|null $start Unix time (in milliseconds) of when the activity started.
 * @property int|null $end   Unix time (in milliseconds) of when the activity ends.
 */
class Timestamps extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'start',
        'end',
    ];
}

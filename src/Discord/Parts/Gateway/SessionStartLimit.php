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

namespace Discord\Parts\Gateway;

use Discord\Parts\Part;

/**
 * Information on the current session start limit.
 *
 * @link https://discord.com/developers/docs/events/gateway#session-start-limit-object
 *
 * @since 10.18.0
 *
 * @property int $total           Total number of session starts the current user is allowed.
 * @property int $remaining       Remaining number of session starts the current user is allowed.
 * @property int $reset_after     Number of milliseconds after which the limit resets.
 * @property int $max_concurrency Number of identify requests allowed per 5 seconds.
 */
class SessionStartLimit extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'total',
        'remaining',
        'reset_after',
        'max_concurrency',
    ];
}

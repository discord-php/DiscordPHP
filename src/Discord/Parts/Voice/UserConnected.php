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

namespace Discord\Parts\Voice;

use Discord\Parts\Part;

class UserConnected extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'user_id',
    ];
}

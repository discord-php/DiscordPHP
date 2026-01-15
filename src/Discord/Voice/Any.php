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

namespace Discord\Voice;

use Discord\Parts\Part;

/**
 * Represents the any data sent when a user connects to a voice channel.
 *
 * Undocumented.
 *
 * @since 10.40.0
 *
 * @property int $any The any data.
 */
class Any extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'any',
    ];
}

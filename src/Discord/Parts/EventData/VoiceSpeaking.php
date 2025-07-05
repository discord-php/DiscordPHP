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

namespace Discord\Parts\EventData;

use Discord\Parts\Part;

class VoiceSpeaking extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'user_id', // undocumented
        'ssrc',
        'speaking',
        'delay', // Should be set to 0 for bots, but may not be set at all on incoming payloads
    ];
}

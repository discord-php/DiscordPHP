<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Part;

class VoiceServerUpdate extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['token', 'guild_id', 'endpoint'];
}

<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * The Game part defines what game the user is playing at the moment.
 */
class Game extends Part
{
    const TYPE_PLAYING   = 0;
    const TYPE_STREAMING = 1;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['name', 'url', 'type'];
}

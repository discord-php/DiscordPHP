<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Http\RateLimit;

class GlobalBucket extends AbstractBucket
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'Global';

    /**
     * {@inheritdoc}
     */
    protected $uses = 50;

    /**
     * {@inheritdoc}
     */
    protected $time = 10;
}

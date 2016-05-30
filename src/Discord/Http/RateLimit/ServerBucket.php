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

use Discord\Parts\Guild\Guild;
use React\EventLoop\LoopInterface;

class ServerBucket extends AbstractBucket
{
    /**
     * {@inheritdoc}
     */
    protected $uses = 5;

    /**
     * {@inheritdoc}
     */
    protected $time = 5;

    /**
     * {@inheritdoc}
     *
     * @param Guild $guild The guild.
     */
    public function __construct(LoopInterface $loop, Guild $guild)
    {
        $this->guild = $guild;
        $this->name = 'Guild '.$guild->name.' - '.$guild->id;

        parent::__construct($loop);
    }
}

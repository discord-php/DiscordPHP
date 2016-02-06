<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Parts\WebSockets\TypingStart as TypingStartPart;

/**
 * Event that is emitted wheh `TYPING_START` is fired.
 */
class TypingStart extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return TypingStartPart The parsed data.
     */
    public function getData($data, $discord)
    {
        return new TypingStartPart((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        return $discord;
    }
}

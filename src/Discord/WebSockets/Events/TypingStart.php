<?php

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Parts\WebSockets\TypingStart as TypingStartPart;

class TypingStart extends Event
{
    /**
     * Returns the formatted data.
     *
     * @param array $data 
     * @param Discord $discord 
     * @return Message 
     */
    public function getData($data, $discord)
    {
        return new TypingStartPart((array) $data, true);
    }

    /**
     * Updates the Discord instance with the new data.
     *
     * @param mixed $data 
     * @param Discord $discord 
     * @return Discord 
     */
    public function updateDiscordInstance($data, $discord)
    {
        return $discord;
    }
}

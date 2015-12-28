<?php

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;

class UserSettingsUpdate extends Event
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
        return $data;
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
        $new = (object) array_merge((array) $discord->user_settings, (array) $data);

        $discord->user_settings = $new;

        return $discord;
    }
}

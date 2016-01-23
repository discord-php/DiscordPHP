<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

class GuildDelete extends Event
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
        $guildPart = new Guild((array) $data, true);

        return $guildPart;
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
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->id) {
                $discord->guilds->pull($index);
            }
        }

        return $discord;
    }
}

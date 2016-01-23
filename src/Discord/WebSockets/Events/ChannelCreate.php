<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;

class ChannelCreate extends Event
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
        return new Channel((array) $data, true);
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
            if ($guild->id == $data->guild_id) {
                $guild->channels->push($data);
                $discord->guilds->pull($index);
                $discord->guilds->push($guild);

                break;
            }
        }

        return $discord;
    }
}

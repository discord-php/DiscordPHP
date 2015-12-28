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
        return new Channel([
            'id'                    => $data->id,
            'name'                  => $data->name,
            'type'                  => $data->type,
            'topic'                 => $data->topic,
            'guild_id'              => $data->guild_id,
            'position'              => $data->position,
            'is_private'            => $data->is_private,
            'last_message_id'       => $data->last_message_id,
            'permission_overwrites' => $data->permission_overwrites
        ], true);
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
                $discord->guilds->pull($index);
                $guild->channels->push($data);
                $discord->guilds->push();

                break;
            }
        }

        return $discord;
    }
}

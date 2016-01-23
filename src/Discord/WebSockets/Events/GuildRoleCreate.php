<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;

class GuildRoleCreate extends Event
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
        $adata = (array) $data->role;
        $adata['guild_id'] = $data->guild_id;
        return new Role($adata, true);
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
                $guild->roles->push($data);

                $discord->guilds->pull($index);
                $discord->guilds->push($guild);

                break;
            }
        }

        return $discord;
    }
}

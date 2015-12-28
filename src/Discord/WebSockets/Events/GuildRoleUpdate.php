<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;

class GuildRoleUpdate extends Event
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
        return new Role([
            'id'            => $data->role->id,
            'name'            => $data->role->name,
            'color'            => $data->role->color,
            'managed'        => $data->role->managed,
            'hoist'            => $data->role->hoist,
            'position'        => $data->role->position,
            'permissions'    => $data->role->permissions,
            'guild_id'        => $data->guild_id
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
                foreach ($guild->roles as $rindex => $role) {
                    if ($role->id == $data->id) {
                        $guild->roles->pull($rindex);
                        $guild->roles->push($data);

                        break;
                    }
                }

                $discord->guilds->pull($index);
                $discord->guilds->push($guild);

                break;
            }
        }

        return $discord;
    }
}

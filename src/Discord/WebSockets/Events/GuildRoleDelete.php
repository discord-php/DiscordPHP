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

class GuildRoleDelete extends Event
{
    /**
     * Returns the formatted data.
     *
     * @param array   $data
     * @param Discord $discord
     *
     * @return Message
     */
    public function getData($data, $discord)
    {
        return $data;
    }

    /**
     * Updates the Discord instance with the new data.
     *
     * @param mixed   $data
     * @param Discord $discord
     *
     * @return Discord
     */
    public function updateDiscordInstance($data, $discord)
    {
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->roles as $rindex => $role) {
                    if ($role->id == $data->role_id) {
                        $guild->roles->pull($rindex);

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

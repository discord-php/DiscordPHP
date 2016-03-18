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

use Discord\Cache\Cache;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `GUILD_ROLE_DELETE` is fired.
 */
class GuildRoleDelete extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return array The data.
     */
    public function getData($data, $discord)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::remove("guild.{$data->guild_id}.roles.{$data->role_id}");

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->roles as $rindex => $role) {
                    if ($role->id == $data->role_id) {
                        $guild->roles->pull($rindex);

                        break;
                    }
                }

                break;
            }
        }

        return $discord;
    }
}

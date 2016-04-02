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
use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `GUILD_ROLE_UPDATE` is fired.
 */
class GuildRoleUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Role The parsed data.
     */
    public function getData($data, $discord)
    {
        $adata             = (array) $data->role;
        $adata['guild_id'] = $data->guild_id;

        return new Role($adata, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::set("guild.{$data->guild_id}.roles.{$data->id}", $data);

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->roles as $rindex => $role) {
                    if ($role->id == $data->id) {
                        $guild->roles[$rindex] = $data;

                        break;
                    }
                }

                break;
            }
        }

        return $discord;
    }
}

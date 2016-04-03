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

use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

/**
 * Event that is emitted when `GUILD_ROLE_UPDATE` is fired.
 */
class GuildRoleUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $guildId        = $data->guild_id;
        $data           = $data->role;
        $data->guild_id = $guildId;

        $data = $this->partFactory->create(Role::class, $data, true);

        $this->cache->set("guild.{$data->guild_id}.roles.{$data->id}", $data);

        foreach ($this->discord->guilds as $index => $guild) {
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

        $deferred->resolve($data);
    }
}

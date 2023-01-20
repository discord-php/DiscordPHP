<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-role-update
 *
 * @since 2.1.3
 */
class GuildRoleUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $role = (array) $data->role + ['guild_id' => $data->guild_id];
        $rolePart = $oldRole = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Role */
            if ($oldRole = yield $guild->roles->cacheGet($data->role->id)) {
                // Swap
                $rolePart = $oldRole;
                $oldRole = clone $oldRole;

                $rolePart->fill($role);
            }
        }

        if ($rolePart === null) {
            /** @var Role */
            $rolePart = $this->factory->part(Role::class, $role, true);
        }

        if (isset($guild)) {
            $guild->roles->set($data->role->id, $rolePart);
        }

        return [$rolePart, $oldRole];
    }
}

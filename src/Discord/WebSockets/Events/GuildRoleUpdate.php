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
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-role-update
 */
class GuildRoleUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $adata = (array) $data->role;
        $adata['guild_id'] = $data->guild_id;
        $rolePart = $oldRole = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldRole = $guild->roles->get('id', $data->role->id)) {
                // Swap
                $rolePart = $oldRole;
                $oldRole = clone $oldRole;

                $rolePart->fill($adata);
            }
        }

        if (! $rolePart) {
            /** @var Role */
            $rolePart = $this->factory->create(Role::class, $adata, true);
            if ($guild = $rolePart->guild) {
                $guild->roles->pushItem($rolePart);
            }
        }

        $deferred->resolve([$rolePart, $oldRole]);
    }
}

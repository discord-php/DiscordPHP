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
 * @see https://discord.com/developers/docs/topics/gateway#guild-role-create
 */
class GuildRoleCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $adata = (array) $data->role;
        $adata['guild_id'] = $data->guild_id;

        /** @var Role */
        $rolePart = $this->factory->create(Role::class, $adata, true);

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $guild->roles->pushItem($rolePart);
        }

        $deferred->resolve($rolePart);
    }
}

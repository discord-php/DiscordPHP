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

use Discord\Helpers\Deferred;
use Discord\WebSockets\Event;

class GuildRoleDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $role = $guild->roles->pull($data->role_id);
            $this->discord->guilds->push($guild);

            $deferred->resolve($role);
        } else {
            $deferred->resolve($data);
        }
    }
}

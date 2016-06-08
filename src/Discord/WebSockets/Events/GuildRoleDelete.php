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

use React\Promise\Deferred;
use Discord\WebSockets\Event;

class GuildRoleDelete extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);
        $guild->roles->pull($data->role_id);

        $deferred->resolve($data);
    }
}

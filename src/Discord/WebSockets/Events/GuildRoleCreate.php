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

class GuildRoleCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $rolePart = $this->factory->create(Role::class, $data, true);

        if ($this->discord->guilds->has($rolePart->guild_id)) {
            $guild = $this->discord->guilds->offsetGet($rolePart->guild_id);
            $guild->roles->offsetSet($rolePart->id, $rolePart);

            $this->discord->guilds->offsetSet($guild->id, $guild);
        }

        $deferred->resolve($rolePart);
    }
}

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

class GuildRoleUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
		$rolePart = $this->factory->create(Role::class, $data, true);
		$old = null;

        if ($this->discord->guilds->has($rolePart->guild_id)) {
			$guild = $this->discord->guilds->offsetGet($rolePart->guild_id);
			$old   = $guild->roles->has($rolePart->id) ? $guild->roles->offsetGet($rolePart->id) : null;
            $guild->roles->offsetSet($rolePart->id, $rolePart);
			
			$this->discord->guilds->offsetSet($guild->id, $guild);
        }

        $deferred->resolve([$rolePart, $old]);
    }
}

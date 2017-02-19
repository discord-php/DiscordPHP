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

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildMemberAdd extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $memberPart = $this->factory->create(Member::class, $data, true);


        if ($this->discord->guilds->has($memberPart->guild_id)) {
			$guild = $this->discord->guilds->offsetGet($memberPart->guild_id);
            $guild->members->offsetSet($memberPart->id, $memberPart);
            ++$guild->member_count;

            $this->discord->guilds->offsetSet($guild->id, $guild);
        }

        $deferred->resolve($memberPart);
    }
}

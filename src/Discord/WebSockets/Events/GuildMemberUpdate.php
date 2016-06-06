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

class GuildMemberUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $memberPart = $this->factory->create(Member::class, $data, true);

        $this->cache->set("guild.{$memberPart->guild_id}.members.{$memberPart->id}", $memberPart);
        $this->cache->set("user.{$memberPart->id}", $memberPart->user);

        $guild = $this->discord->guilds->get('id', $memberPart->guild_id);

        if (! is_null($guild)) {
            $guild->members->push($memberPart);

            $this->discord->guilds->push($guild);
            $this->cache->set("guild.{$guild->id}", $guild);
        }

        $deferred->resolve($memberPart);
    }
}

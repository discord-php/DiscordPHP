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
use Discord\Repository\Guild\MemberRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildSync extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
		$guild = $this->discord->guilds->offsetGet($data->id);
		
		$members = new MemberRepository(
            $this->http,
            $this->cache,
            $this->factory,
            $guild->getRepositoryAttributes()
        );
		
		foreach ($data->members as $member) {
            $memberPart = $this->factory->create(Member::class, [
                'user'      => $member->user,
                'roles'     => $member->roles,
                'mute'      => $member->mute,
                'deaf'      => $member->deaf,
                'joined_at' => $member->joined_at,
                'nick'      => (property_exists($member, 'nick')) ? $member->nick : null,
                'guild_id'  => $data->id,
                'status'    => 'offline',
                'game'      => null,
            ], true);

            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member->user->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game   = $presence->game;
                }
            }

            $this->discord->users->offsetSet($memberPart->id, $memberPart->user);
            $members->offsetSet($memberPart->id, $memberPart);
        }
		
		$guild->large = $data->large;
		$guild->members = $members;
		
		$resolve = function () use (&$guild, $deferred) {
            if ($guild->large) {
                $this->discord->addLargeGuild($guild);
            }

            $this->discord->guilds->offsetSet($guild->id, $guild);

            $deferred->resolve($guild);
        };
		
		$resolve();
    }
}

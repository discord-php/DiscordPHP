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
use Discord\Parts\User\User;
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
		
		$guild->large   = $data->large;
		
		if ($this->discord->options['storeMembers'] || $this->discord->options['storeUsers']) {
			if ($this->discord->options['storeMembers']) {
				$presences = [];
				foreach ($data->presences as $presence) {
					$presences[$presence->user->id] = $presence;
				}
			}
			foreach ($data->members as $member) {
				if ($this->discord->options['storeMembers']) {
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

					if (array_key_exists($member->user->id, $presences))
					{
						$presence = $presences[$member->user->id];
						$memberPart->status = $presence->status;
						$memberPart->game   = $presence->game;
					}
					
					$guild->members->offsetSet($member->user->id, $memberPart);
				}
				if ($this->discord->options['storeUsers']) {
					$user = $this->factory->create(User::class, $member->user, true);
					$this->discord->users->offsetSet($user->id, $user);
				}
			}
		}

		if ($guild->large)
		{
			$this->discord->addLargeGuild($guild);
		}

		$deferred->resolve($guild);
    }
}

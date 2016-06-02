<?php

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

		$this->cache->set("guild.{$memberPart->guild_id}.members.{$memberPart->id}", $memberPart);
		$this->cache->set("user.{$memberPart->id}", $memberPart->user);

		$guild = $this->discord->guilds->get('id', $memberPart->guild_id);

		$guild->members->push($memberPart);
		++$guild->member_count;

		$this->discord->guilds->push($guild);
		$this->cache->set("guild.{$guild->id}");

		$deferred->resolve($memberPart);
	}
}
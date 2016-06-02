<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildMemberRemove extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$memberPart = $this->factory->create(Member::class, $data, true);

		$this->cache->remove("guild.{$memberPart->guild_id}.members.{$memberPart->id}");
		$this->cache->remove("user.{$memberPart->id}");

		$guild = $this->discord->guilds->get('id', $memberPart->guild_id);

		$guild->members->pull($memberPart->id);
		--$guild->member_count;

		$this->discord->guilds->push($guild);
		$this->cache->set("guild.{$guild->id}");

		$deferred->resolve($memberPart);
	}
}
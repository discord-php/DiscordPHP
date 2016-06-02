<?php

namespace Discord\WebSockets\Events;

use React\Promise\Deferred;
use Discord\WebSockets\Event;

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

		$guild->members->push($memberPart);

		$this->discord->guilds->push($guild);
		$this->cache->set("guild.{$guild->id}");

		$deferred->resolve($memberPart);
	}
}
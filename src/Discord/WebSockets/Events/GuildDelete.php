<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildDelete extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$guildPart = $this->factory->create(Guild::class, $data, true);

		$this->cache->remove("guild.{$guildPart->id}");
		$this->discord->guilds->pull($guildPart->id);

		$deferred->resolve($guildPart);	
	}
}
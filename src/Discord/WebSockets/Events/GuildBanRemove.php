<?php

namespace Discord\WebSockets\Events;

use React\Promise\Deferred;
use Discord\WebSockets\Event;

class GuildBanRemove extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$guild = $this->discord->guilds->get('id', $data->guild_id);
		$ban = $this->factory->create(Ban::class, [
			'guild' => $guild,
			'user'  => $data->user,
		], true);

		$this->cache->remove("ban.{$ban->guild->id}.bans.{$ban->user->id}");

		$guild = $this->discord->guilds->get('id', $ban->guild->id);
		$guild->bans->pull($ban->id);

		$deferred->resolve($ban);
	}
}
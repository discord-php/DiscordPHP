<?php

namespace Discord\WebSockets\Events;

use React\Promise\Deferred;
use Discord\WebSockets\Event;

class GuildRoleDelete extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$this->cache->remove("guild.{$data->guild_id}.roles.{$data->role_id}");

		$guild = $this->discord->guilds->get('id', $data->guild_id);
		$guild->roles->pull($data->role_id);

		$deferred->resolve($data);
	}
}
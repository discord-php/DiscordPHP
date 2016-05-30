<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class ChannelDelete extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$channel = $this->factory->create(Channel::class, $data);
		$this->cache->remove("channel.{$channel->id}");

        $guild = $this->discord->guilds->get('id', $channel->guild_id);
        $guild->channels->pull($channel->id);

        $deferred->resolve($channel);
	}
}
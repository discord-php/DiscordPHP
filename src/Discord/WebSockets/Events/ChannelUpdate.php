<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class ChannelUpdate extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$channel = $this->factory->create(Channel::class, $data, true);
		$this->cache->set("channel.{$channel->id}", $channel);

		if ($channel->is_private) {
			$this->discord->private_channels->push($channel);
		} else {
            $guild = $this->discord->guilds->get('id', $channel->guild_id);
            $guild->channels->push($channel);
		}

		$deferred->resolve($channel);
	}
}
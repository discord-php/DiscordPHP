<?php

namespace Discord\WebSockets\Events;

use React\Promise\Deferred;
use Discord\WebSockets\Event;

class MessageDelete extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$this->cache->remove("message.{$data->id}");

		$channel = $this->cache->get("channel.{$data->channel_id}");
		$channel->messages->pull($data->id);
	}
}
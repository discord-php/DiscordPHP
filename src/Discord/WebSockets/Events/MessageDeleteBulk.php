<?php

namespace Discord\WebSockets\Events;

use React\Promise\Deferred;
use Discord\WebSockets\Event;

class MessageDeleteBulk extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$channel = $this->cache->get("channel.{$data->channel_id}");

		foreach ($data->ids as $message) {
			$channel->messages->pull($message);
			$this->cache->remove("message.{$message}");
		}

		$deferred->resolve($data);
	}
}
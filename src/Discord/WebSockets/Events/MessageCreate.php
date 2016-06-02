<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class MessageCreate extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$messagePart = $this->factory->create(Message::class, $data, true);

		$this->cache->set("message.{$messagePart->id}", $messagePart);

		$channel = $this->cache->get("channel.{$messagePart->channel_id}");
		$channel->messages->push($messagePart);

		$deferred->resolve($messagePart);
	}
}
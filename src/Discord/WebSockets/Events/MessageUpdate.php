<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class MessageUpdate extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$messagePart = $this->factory->create(Message::class, $data, true);

		$channel = $this->cache->get("channel.{$messagePart->channel_id}");
		$message = $channel->messages->get('id', $messagePart->id);

		if (is_null($message)) {
			$newMessage = $messagePart;
		} else {
			$newMessage = $this->factory->create(Message::class, array_merge($message->getPublicAttributes(), $messagePart->getPublicAttributes()), true);
		}

		$channel->messages->push($newMessage);

		$deferred->resolve($messagePart);
	}
}
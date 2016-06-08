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
		$messages = $this->discord->getRepository(
            MessageRepository::class,
            $data->channel_id,
            'messages',
            ['channel_id' => $data->channel_id]
        );

		foreach ($data->ids as $message) {
			$messages->pull($message);
		}

		$deferred->resolve($data);
	}
}
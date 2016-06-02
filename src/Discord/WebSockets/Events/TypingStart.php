<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\TypingStart as TypingStartPart;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class TypingStart extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$typing = $this->factory->create(TypingStartPart::class, $data, true);

		$deferred->resolve($typing);
	}
}
<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\VoiceServerUpdate as VoiceServerUpdatePart;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class VoiceServerUpdate extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$part = $this->factory->create(VoiceServerUpdatePart::class, $data, true);

		$deferred->resolve($part);
	}
}
<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\PresenceUpdate as PresenceUpdatePart;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class PresenceUpdate extends Event
{
	/**
	 * {@inheritdoc}
	 */
	public function handle(Deferred $deferred, $data)
	{
		$presenceUpdate = $this->factory->create(PresenceUpdatePart::class, $data, true);

		$guild = $this->discord->guilds->get('id', $presenceUpdate->guild_id);
		$member = $guild->members->get('id', $presenceUpdate->user->id);

		if (! is_null($member)) {
			$member->game = $presenceUpdate->game;
			$member->status = $presenceUpdate->status;
			$guild->members->push($member);
		}

		$deferred->resolve($presenceUpdate);
	}
}
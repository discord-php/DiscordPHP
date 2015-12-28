<?php

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;

class PresenceUpdate extends Event
{
	/**
	 * Returns the formatted data.
	 *
	 * @param array $data 
	 * @param Discord $discord 
	 * @return Message 
	 */
	public function getData($data, $discord)
	{
		return $data;
	}

	/**
	 * Updates the Discord instance with the new data.
	 *
	 * @param mixed $data 
	 * @param Discord $discord 
	 * @return Discord 
	 */
	public function updateDiscordInstance($data, $discord)
	{
		foreach ($discord->guilds as $index => $guild) {
			if ($guild->id == $data->guild_id) {
				foreach ($guild->members as $mindex => $member) {
					if ($member->id == $data->user->id) {
						$user->game = $data->game;
						$user->status = $data->status;

						$guild->members->pull($mindex);
						$guild->members->push($member);

						break;
					}
				}

				$discord->guilds->pull($index);
				$discord->guilds->push($guild);

				break;
			}
		}

		return $discord;
	}
}
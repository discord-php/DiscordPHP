<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Ban;
use Discord\WebSockets\Event;

class GuildBanAdd extends Event
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
		$guild = $discord->guilds->get('id', $data->guild_id);

		return new Ban([
			'guild'	=> $guild,
			'user'	=> $data->user
		], true);
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
		$discord->guilds->get('id', $data->guild_id)->bans->push($data);

		foreach ($discord->guilds->get('id', $data->guild_id)->members as $index => $member) {
			if ($member->id == $data->user_id) {
				$discord->guilds->get('id', $data->guild_id)->members->pull($index);
			}
		}

		return $discord;
	}
}
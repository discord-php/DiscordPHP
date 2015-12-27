<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Ban;
use Discord\WebSockets\Event;

class GuildBanRemove extends Event
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

		foreach ($discord->guilds->get('id', $data->guild_id)->bans as $index => $ban) {
			if ($ban->user_id == $data->user_id) {
				$discord->guilds->get('id', $data->guild_id)->bans->pull($index);
			}
		}

		foreach ($discord->guilds as $index => $guild) {
			if ($guild->id == $data->guild_id) {
				foreach ($guild->bans as $bindex => $ban) {
					if ($ban->user_id == $data->user_id) {
						$guild->bans->pull($bindex);
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
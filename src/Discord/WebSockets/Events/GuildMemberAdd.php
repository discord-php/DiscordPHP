<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;

class GuildMemberAdd extends Event
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
		return new Member([
			'user'		=> $data->user,
			'roles'		=> $data->roles,
			'guild_id'	=> $data->guild_id,
			'joined_at'	=> $data->joined_at
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
		$discord->guilds->get('id', $data->guild_id)->members->push($data);

		return $discord;
	}
}
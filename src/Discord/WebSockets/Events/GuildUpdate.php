<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

class GuildUpdate extends Event
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
		return new Guild([
			'id'                => $data->id,
            'name'              => $data->name,
            'icon'              => $data->icon,
            'region'            => $data->region,
            'owner_id'          => $data->owner_id,
            'roles'             => $data->roles,
            'joined_at'         => $data->joined_at,
            'afk_channel_id'    => $data->afk_channel_id,
            'afk_timeout'       => $data->afk_timeout,
            'embed_enabled'     => $data->embed_enabled,
            'embed_channel_id'  => $data->embed_channel_id,
            'features'          => $data->features,
            'splash'            => $data->splash,
            'emjojis'           => $data->emojis
		]);
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
			if ($guild->id == $data->id) {
				$discord->guilds->pull($index);
				$discord->guilds->push($guild);

				break;
			}
		}

		return $discord;
	}
}
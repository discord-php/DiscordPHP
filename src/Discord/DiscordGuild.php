<?php

namespace Discord;

use Discord\Guzzle;
use Discord\DiscordChannel;

class DiscordGuild
{
	protected $guild_id;
	protected $name;
	protected $client;

	public function __construct($guild_id, $name, $client)
	{
		$this->guild_id = $guild_id;
		$this->name = $name;
		$this->client = $client;
	}

	/**
	 * Returns an array of all the channels
	 * @return array [Discord\DiscordChannel]
	 */
	public function getChannels()
	{
		$request = Guzzle::get('guilds/'.$this->guild_id.'/channels', [
			'headers' => [
				'authorization' => $this->client->token
			]
		]);

		$channels = json_decode($request->getBody()->getContents());
		$channel_instances = [];

		foreach($channels as $channel)
		{
			$channel_instances[] = new DiscordChannel($this, $channel->id, $channel->name, $channel->is_private, $this->client);
		}

		return $channel_instances;
	}
}
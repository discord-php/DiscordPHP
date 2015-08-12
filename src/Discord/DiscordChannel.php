<?php

namespace Discord;

use Discord\Guzzle;

class DiscordChannel
{
	protected $guild;
	protected $channel_id;
	protected $channel_name;
	protected $channel_private;
	protected $client;

	public function __construct($guild, $channel_id, $channel_name, $channel_private, $client)
	{
		$this->guild = $guild;
		$this->channel_id = $channel_id;
		$this->channel_name = $channel_name;
		$this->channel_private = $channel_private;
		$this->client = $client;
	}

	/**
	 * Sends a message to the channel
	 * @param string
	 */
	public function sendMessage($message)
	{
		try {
			$response = Guzzle::post('channels/' . $this->channel_id . '/messages', [
				'headers' => [
					'authorization' => $this->client->token
				],
				'json' => [
					'content' => $message
				]
			]);
		} catch (Exception $e) {
			// todo catch and throw
		}

		return $response;
	}
}
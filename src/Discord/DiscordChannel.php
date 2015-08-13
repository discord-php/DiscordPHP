<?php

namespace Discord;

use Discord\Guzzle;
use Discord\Exceptions\MessageFailException;

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
	 * @param Message [string]
	 * @param Mention User ID [string]
	 * @return GuzzleResponse
	 */
	public function sendMessage($message, $mention = null)
	{
		try {
			$response = Guzzle::post('channels/' . $this->channel_id . '/messages', [
				'headers' => [
					'authorization' => $this->client->getToken()
				],
				'json' => [
					'content' => (!is_null($mention)) ? '<@' . $mention . '> ' . $message : $message
				]
			]);
		} catch (Exception $e) {
			throw new MessageFailException($e);
		}

		return $response;
	}

	/**
	 * Returns the channel guild
	 * @return DiscordGuild
	 */
	public function getGuild()
	{
		return $this->guild;
	}

	/**
	 * Gets the channel ID
	 * @return integer
	 */
	public function getChannelId()
	{
		return $this->channel_id;
	}

	/**
	 * Gets the channel name
	 * @return string
	 */
	public function getChannelName()
	{
		return $this->channel_name;
	}

	/**
	 * Checks if the channel is private
	 * @return boolean
	 */
	public function isPrivate()
	{
		return $this->channel_private;
	}
}
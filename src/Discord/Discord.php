<?php

namespace Discord;

use Discord\Exceptions\DiscordLoginFailedException;
use Discord\Guzzle;
use GuzzleHttp\Exception\ClientException;

class Discord
{
	protected $client;

	public function __construct($email_address, $password)
	{
		$this->client = $this->attemptLogin($email_address, $password);
	}

	/**
	 * Attempts to log in to the Discord servers
	 * @param Email Address
	 * @param Password
	 * @return boolean
	 */
	public function attemptLogin($email, $password)
	{
		try {
			$response = Guzzle::post('auth/login', [
				'body' => [
					'email' => $email,
					'password' => $password
				]
			]);
		} catch (CLientException $e) {
			throw new DiscordLoginFailedException($e);
		}
		
		$decoded = json_decode($response->getBody()->getContents());
		if(!@$decoded->token) throw new DiscordLoginFailedException('The login attempt failed.');

		try {
			$response = Guzzle::get('users/@me', [
				'headers' => [
					'authorization' => $decoded->token
				]
			]);
		} catch (Exception $e) {
			throw new DiscordLoginFailedException($e->getMessage());
		} 

		$user = json_decode($response->getBody()->getContents());

		return new DiscordClient($decoded->token, $user->id);
	}

	/**
	 * Returns an array of guilds
	 * @param Discord User ID
	 * @return array [DiscordGuild]
	 */
	public function getGuilds()
	{
		$request = Guzzle::get('users/'.$this->client->getClientId().'/guilds', [
			'headers' => [
				'authorization' => $this->client->getToken()
			]
		]);
		
		$decoded = json_decode($request->getBody()->getContents());
		$guilds = [];

		foreach($decoded as $guild)
		{
			$guilds[] = new DiscordGuild($guild->id, $guild->name, $this->client);
		}

		return $guilds;
	}

	/**
	 * Finds a channel by ID
	 * @param Channel ID
	 * @return DiscordChannel
	 */
	public function findChannel($id)
	{
		$request = Guzzle::get('channels/'.$id, [
			'headers' => [
				'authorization' => $this->client->getToken()
			]
		]);

		$decoded = json_decode($request->getBody()->getContents());
		$channel = new DiscordChannel($this->findGuild($decoded->guild_id), $decoded->id, $decoded->name, $decoded->is_private, $this->client);

		return $channel;
	}

	/**
	 * Finds a guild by ID
	 * @param Guild ID
	 * @return DiscordGuild
	 */
	public function findGuild($id)
	{
		$request = Guzzle::get('guilds/'.$id, [
			'headers' => [
				'authorization' => $this->client->getToken()
			]
		]);

		$decoded = json_decode($request->getBody()->getContents());
		$guild = new DiscordGuild($decoded->id, $decoded->name, $this->client);
		
		return $guild;
	}
}

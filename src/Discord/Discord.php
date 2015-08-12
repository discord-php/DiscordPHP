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
	 * @return boolean
	 */
	public function attemptLogin($email, $password)
	{
		$guzzleclient = new \GuzzleHttp\Client(['base_uri' => 'https://discordapp.com/api/']);

		try {
			$response = $guzzleclient->post('auth/login', [
				'form_params' => [
					'email' => $email,
					'password' => $password
				]
			]);
		} catch (CLientException $e) {
			throw new DiscordLoginFailedException($e);
		}
		
		$decoded = json_decode($response->getBody()->getContents());
		if(!@$decoded->token) throw new DiscordLoginFailedException('The login attempt failed.');

		return new DiscordClient($decoded->token);
	}

	/**
	 * Returns an array of guilds
	 * @param Discord User ID
	 * @return array [DiscordGuild]
	 */
	public function getGuilds($id)
	{
		$request = Guzzle::get('users/'.$id.'/guilds', [
			'headers' => [
				'authorization' => $this->client->token
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
	 */
	public function findChannel($id)
	{
		$request = Guzzle::get('channels/'.$id, [
			'headers' => [
				'authorization' => $this->client->token
			]
		]);

		$decoded = json_decode($request->getBody()->getContents());
		$channel = new DiscordChannel($this->findGuild($decoded->guild_id), $decoded->id, $decoded->name, $decoded->is_private, $this->client);

		return $channel;
	}

	/**
	 * Finds a guild by ID
	 * @param Guild ID
	 */
	public function findGuild($id)
	{
		$request = Guzzle::get('guilds/'.$id, [
			'headers' => [
				'authorization' => $this->client->token
			]
		]);

		$decoded = json_decode($request->getBody()->getContents());
		$guild = new DiscordGuild($decoded->id, $decoded->name, $this->client);
		
		return $guild;
	}

	/**
	 * Creates a new Guild (Server)
	 * @param string Name
	 */
	public function createGuild($name)
	{
		$request = Guzzle::post('guilds', [
			'headers' => [
				'authorization' => $this->client->token
			],
			'json' => [

			]
		]);

	}

	/**
	 * Returns an instance of a client
	 * @return Discord\DiscordClient
	 */
	public function getClient()
	{
		return $this->client;
	}
}

<?php

namespace Discord;

use Discord\Exceptions\DiscordLogoutFailedException;
use GuzzleHttp\Exception\ClientException;

class DiscordClient
{
	protected $token;
	protected $client_id;
	protected $logged_in;

	public function __construct($token, $client_id)
	{
		$this->token = $token;
		$this->client_id = $client_id;
		$this->logged_in = true;
	}

	/**
	 * Logs out of Discord
	 * @return boolean
	 */
	public function logout()
	{
		try {
			$response = Guzzle::post('auth/logout');
		} catch (Exception $e) {
			throw new DiscordLogoutFailedException($e->getMessage());
		}

		$this->logged_in = false;
		return true;
	}

	/**
	 * Returns the authentication token
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * Returns the user ID
	 * @return integer
	 */
	public function getClientId()
	{
		return $this->client_id;
	}

	/**
	 * Checks if the user is logged in
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return $this->logged_in;
	}
}

<?php

namespace Discord;

use Discord\Exceptions\DiscordLogoutFailedException;
use GuzzleHttp\Exception\ClientException;

class DiscordClient extends DiscordEntity
{
	public $token;
	protected $logged_in;

	public function __construct($token)
	{
		$this->token = $token;
		$this->logged_in = true;
	}

	public function logout()
	{
		$guzzleclient = new \GuzzleHttp\Client(['base_uri' => 'https://discordapp.com/api/']);

		try {
			$response = $guzzleclient->post('auth/logout');
		} catch (CLientException $e) {
			throw new DiscordLogoutFailedException($e);
		}

		$this->logged_in = false;
	}
}

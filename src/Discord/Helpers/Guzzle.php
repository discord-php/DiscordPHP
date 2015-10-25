<?php

namespace Discord\Helpers;

use Discord\Exceptions\DiscordRequestFailedException;
use GuzzleHttp\Client as GuzzleClient;

class Guzzle
{
	protected $base_url;
	protected $guzzle;
	protected $token;

	public function __construct($base_url = 'https://discordapp.com/api')
	{
		$this->base_url = $base_url;	
		$this->guzzle = new GuzzleClient();
	}

	/**
	 * Sends a GET request to the specified URL
	 *
	 * @param string $url
	 * @param array $params 
	 * @param boolean $noauth
	 */
	public function get($url, $params = [], $noauth = false)
	{
		$headers = [];

		if (!$noauth) {
			$headers['authorization'] = $this->token;
		}

		try {
			$request = $this->guzzle->request('GET', "{$this->base_url}/{$url}", [
				'form_params' => $params,
				'headers' => $headers
			]);

			if ($request->getStatusCode() != 200) {
				$this->handleError($request->getStatusCode());
			}
		} catch (\RuntimeException $e) {
			$this->handleError($e->getCode());
		}

		return $request;
	}

	/**
	 * Sends a POST request to the specified URL
	 *
	 * @param string $url
	 * @param array $params 
	 * @param boolean $noauth
	 */
	public function post($url, $params = [], $noauth = false)
	{
		$headers = [];

		if (!$noauth) {
			$headers['authorization'] = $this->token;
		}

		try {
			$request = $this->guzzle->request('POST', "{$this->base_url}/{$url}", [
				'form_params' => $params,
				'headers' => $headers
			]);

			if ($request->getStatusCode() != 200) {
				$this->handleError($request->getStatusCode());
			}
		} catch (\RuntimeException $e) {
			$this->handleError($e->getCode());
		}

		return $request;
	}

	/**
	 * Handles an error code.
	 *
	 * @param integer $error_code 
	 */
	public function handleError($error_code)
	{
		switch ($error_code) {
			case 400:
				throw new DiscordRequestFailedException("Error code {$error_code}: This usually means you have entered an incorrect Email or Password.");
				break;
			default:
				throw new DiscordRequestFailedException("Erorr code {$error_code}: There was an error processing the request.");
				break;
		}
	}

	/**
	 * Returns the token for the currently logged in user.
	 *
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;	
	}

	/**
	 * Sets the token for the currently logged in user.
	 * 
	 * @param string $token 
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}
}
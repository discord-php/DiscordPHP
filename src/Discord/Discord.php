<?php

namespace Discord;

use Discord\Exceptions\LoginFailedException;
use Discord\Helpers\Guzzle;
use Discord\Parts\Client;

class Discord
{
	protected $guzzle;
	protected $client;

	public function __construct($email, $password)
	{
		$this->guzzle = new Guzzle;

		$request = $this->guzzle->post('auth/login', [
			'email' => $email,
			'password' => $password
		]);

		$token = json_decode($request->getBody())->token;
		$this->guzzle->setToken($token);

		$request = json_decode($this->guzzle->get('users/@me')->getBody());
		$this->client = new Client(
			$request->id,
			$request->username,
			$request->email,
			$request->verified,
			$request->avatar, 
			$this->guzzle
		);
	}

	/**
	 * Returns the bare guzzle interface.
	 *
	 * @return Guzzle
	 */
	public function getGuzzle()
	{
		return $this->guzzle;
	}

	/**
	 * Returns the currently logged in client.
	 *
	 * @return Client 
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Handles dynamic calls to the class.
	 *
	 * @return mixed 
	 */
	public function __call($name, $args)
	{
		return call_user_func_array([$this->client, $name], $args);
	}

	/**
	 * Handles dynamic variable calls to the class.
	 *
	 * @return mixed 
	 */
	public function __get($name)
	{
		return $this->client->{$name};
	}
}
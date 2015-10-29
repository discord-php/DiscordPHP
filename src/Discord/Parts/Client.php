<?php

namespace Discord\Parts;

use Discord\Parts\User;

class Client
{
	public $id;
	public $username;
	public $email;
	public $verified;
	public $avatar;
	public $user;
	protected $guzzle;

	public function __construct($id, $username, $email, $verified, $avatar, $guzzle)
	{
		$this->id = $id;
		$this->username = $username;
		$this->email = $email;
		$this->verified = $verified;
		$this->avatar = $avatar;

		$this->guzzle = $guzzle;

		$request = json_decode($this->guzzle->get("users/{$this->id}")->getBody());

		$this->user = new User(
			$request->id,
			$request->username,
			$request->avatar,
			$this->guzzle
		);
	}

	/**
	 * Handles dynamic calls to the class.
	 *
	 * @return mixed 
	 */
	public function __call($name, $args)
	{
		return call_user_func_array([$this->user, $name], $args);
	}

	/**
	 * Handles dynamic variable calls to the class.
	 *
	 * @return mixed 
	 */
	public function __get($name)
	{
		return $this->user->{$name};
	}
}
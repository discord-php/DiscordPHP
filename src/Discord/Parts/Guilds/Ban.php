<?php

namespace Discord\Parts\Guilds;

use Discord\Parts\User;

class Ban
{
	public $user;
	protected $guzzle;

	public function __construct($user, $guzzle)
	{
		$this->user = new User(
			$user->id,
			$user->username,
			$user->avatar,
			$this->guzzle
		);

		$this->guzzle = $guzzle;
	}
}
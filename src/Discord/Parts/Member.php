<?php

namespace Discord\Parts;

use Discord\Parts\User;

class Member
{
	public $id;
	public $user;
	public $deaf;
	public $mute;
	public $joined_at;
	protected $guzzle;

	public function __construct($id, $user, $deaf, $mute, $joined_at, $guzzle)
	{
		$this->id = $id;
		$this->user = $user;
		$this->deaf = $deaf;
		$this->mute = $mute;
		$this->joined_at = new \DateTime($joined_at);
		$this->guzzle = $guzzle;
	}
}
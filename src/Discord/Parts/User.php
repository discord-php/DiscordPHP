<?php

namespace Discord\Parts;

use Discord\Parts\Guilds\Guild;

class User
{
	public $id;
	public $username;
	public $avatar;
	protected $guzzle;

	public function __construct($id, $username, $avatar, $guzzle)
	{
		$this->id = $id;
		$this->username = $username;
		$this->avatar = $avatar;

		$this->guzzle = $guzzle;
	}

	/**
	 * Returns an array of guilds.
	 *
	 * @return array 
	 */
	public function getGuilds()
	{
		if (isset($this->guilds)) return $this->guilds;

		$request = json_decode($this->guzzle->get("users/{$this->id}/guilds"));

		foreach ($request as $guild) {
			$this->guilds[$guild->id] = new Guild(
				$guild->id,
				$guild->name,
				$guild->region,
				$guild->owner_id,
				$guild->icon,
				$guild->created,
				$this->guzzle
			);
		}

		return $this->guilds;
	}
}
<?php

namespace Discord\Parts\Guilds;

use Discord\Parts\Guild\Channel;
use Discord\Parts\Guilds\Ban;
use Discord\Parts\Member;
use Discord\Parts\User;

class Guild
{
	public $id;
	public $name;
	public $region;
	public $owner_id;
	public $icon;
	public $created;
	public $roles;
	public $bans;
	public $channels;
	protected $guzzle;

	public function __construct($id, $name, $region, $owner_id, $icon, $created, $guzzle)
	{
		$this->id = $id;
		$this->name = $name;
		$this->region = $region;
		$this->owner_id = $owner_id;
		$this->icon = $icon;
		$this->created = new \DateTime($created);

		$this->guzzle = $guzzle;

		$request = json_decode($this->guzzle->get("users/{$this->owner_id}")->getBody());
		$this->owner = new User(
			$request->id,
			$request->username,
			$request->avatar,
			$this->guzzle
		);
	}

	/**
	 * Returns an array of bans.
	 * 
	 * @return array 
	 */
	public function getBans()
	{
		if (isset($this->bans)) return $this->bans;

		$request = json_decode($this->guzzle->get("guilds/{$this->id}/bans")->getBody());

		foreach ($request as $index => $ban) {
			$this->bans[$index] = new Ban(
				$ban->user,
				$this->guzzle
			);
		}

		return $this->bans;
	}

	/**
	 * Returns an array of channels.
	 *
	 * @return array 
	 */
	public function getChannels()
	{
		if (isset($this->channels)) return $this->channels;

		$request = json_decode($this->guzzle->get("guilds/{$this->id}/bans")->getBody());

		foreach ($request as $channel) {
			$this->channels[$channel->id] = new Channel(
				$channel->id,
				$channel->name,
				$channel->type,
				$this,
				$channel->is_private,
				$channel->position,
				$channel->last_message_id,
				$this->guzzle
			);
		}	

		return $this->channels;
	}

	/**
	 * Returns an array of members.
	 *
	 * @return array 
	 */
	public function getMembers()
	{
		if (isset($this->members)) return $this->members;

		$request = json_decode($this->guzzle->get("guilds/{$this->id}/members")->getBody());

		foreach ($request as $member) {
			$this->members[$member->user->id] = new Member(
				$member->user->id,
				new User(
					$member->user->id,
					$member->user->username,
					$member->user->avatar
				),
				$member->deaf,
				$member->mute,
				$member->joined_at,
				$this->guzzle
			);
		}

		return $this->members;
	}

	/**
	 * Returns an array of roles.
	 *
	 * @return array 
	 */
	public function getRoles()
	{
		if (isset($this->roles)) return $this->roles;

		$request = json_decode($this->guzzle->get("guilds/{$this->id}/roles")->getBody());

		foreach ($request as $role) {
			$this->roles[$role->id] = new Role(
				$role->id,
				$role->name,
				$role->color,
				$role->managed,
				$role->permissions,
				$role->position,
				$role->hoist,
				$this->guzzle
			);
		}

		return $this->roles;
	}
}
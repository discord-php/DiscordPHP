<?php

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;

class Application extends Part
{
	/**
	 * {@inheritdoc}
	 */
	protected $fillable = ['id', 'name', 'description', 'icon'];

	/**
	 * Returns the invite URL for the application.
	 *
	 * @param int $permissions Permissions to set.
	 * 
	 * @return string Invite URL.
	 */
	public function getInviteURLAttribute($permissions = 0)
	{
		return "https://discordapp.com/oauth2/authorize?client_id={$this->id}&scope=bot&permissions={$permissions}";	
	}
}
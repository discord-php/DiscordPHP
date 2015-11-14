<?php

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\User\User;

class Ban extends Part
{
	/**
	 * Is the part editable?
	 *
	 * @var boolean 
	 */
	public $editable = false;

	/**
	 * The parts fillable attributes.
	 *
	 * @var array 
	 */
	protected $fillable = ['user', 'guild'];

	/**
	 * URIs used to get/create/update/delete the part.
	 *
	 * @var array 
	 */
	protected $uris = [
		'create'	=> 'guilds/:guild_id/bans/:user_id',
		'delete'	=> 'guilds/:guild_id/bans/:user_id'
	];

	/**
	 * Returns the guild id attribute.
	 *
	 * @return integer 
	 */
	public function getGuildIdAttribute()
	{
		return $this->guild->id;
	}

	/**
	 * Returns the user id attribute.
	 *
	 * @return integer 
	 */
	public function getUserIdAttribute()
	{
		return $this->user->id;
	}

	/**
	 * Sets the user attribute.
	 *
	 * @param mixed $value 
	 * @return User
	 */
	public function setUserAttribute($value)
	{
		return new User([
			'id'			=> $value->id,
			'username'		=> $value->username,
			'avatar'		=> $value->avatar,
			'discriminator'	=> $value->discriminator
		]);
	}

	/**
	 * Returns the attributes needed to create.
	 *
	 * @return array 
	 */
	public function getCreatableAttributes()
	{
		return [];
	}

	/**
	 * Returns the attributes needed to edit.
	 *
	 * @return array 
	 */
	public function getUpdatableAttributes()
	{
		return [];
	}
}
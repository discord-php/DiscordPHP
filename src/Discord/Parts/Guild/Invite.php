<?php

namespace Discord\Parts\Guild;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class Invite extends Part
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
	protected $fillable = ['code', 'max_age', 'guild', 'revoked', 'created_at', 'temporary', 'uses', 'max_uses', 'inviter', 'xkcdpass', 'channel'];

	/**
	 * URIs used to get/create/update/delete the part.
	 *
	 * @var array 
	 */
	protected $uris = [
		'create'	=> 'channels/:channel_id/invites',
		'delete'	=> 'invite/:id'
	];

	/**
	 * Returns the guild attribute.
	 *
	 * @return Guild 
	 */
	public function getGuildAttribute()
	{
		return new Guild([
			'id'	=> $this->attributes['guild']->id,
			'name'	=> $this->attributes['guild']->name
		], true);
	}

	/**
	 * Returns the channel attribute.
	 *
	 * @return Channel 
	 */
	public function getChannelAttribute()
	{
		return new Channel([
			'id'	=> $this->attributes['channel']->id,
			'name'	=> $this->attributes['channel']->name,
			'type'	=> $this->attributes['channel']->type
		], true);
	}

	/**
	 * Returns the channel id attribute.
	 *
	 * @return integer 
	 */
	public function getChannelIdAttribute()
	{
		return $this->channel->id;
	}

	/**
	 * Returns the inviter attribute.
	 *
	 * @return User 
	 */
	public function getInviterAttribute()
	{
		return new User([
			'id'			=> $this->attributes['inviter']->id,
			'username'		=> $this->attributes['inviter']->username,
			'avatar'		=> $this->attributes['inviter']->avatar,
			'discriminator'	=> $this->attributes['inviter']->discriminator
		], true);
	}

	/**
	 * Returns the created at attribute.
	 *
	 * @return DateTime
	 */
	public function getCreatedAtAttribute()
	{
		return new \DateTime($this->attributes['created_at']);
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
}
<?php

namespace Discord\Parts\WebSockets;

use Discord\Cache\Cache;
use Discord\Parts\Part;
use Discord\Parts\User\Member;

class VoiceStateUpdate extends Part
{
	/**
	 * {@inheritdoc}
	 */
	protected $fillable = ['channel_id', 'deaf', 'guild_id', 'mute', 'self_deaf', 'self_mute', 'session_id', 'supress', 'token', 'user_id'];

	/**
	 * Gets the member attribute.
	 *
	 * @return Member|null The member attribute.
	 */
	public function getMemberAttribute()
	{
		return Cache::get("guild.{$this->guild_id}.members.{$this->user_id}");
	}

	/**
	 * Gets the channel attribute.
	 *
	 * @return Channel|null The channel attribute.
	 */
	public function getChannelAttribute()
	{
		return Cache::get("channels.{$this->channel_id}");
	}

	/**
	 * Gets the guild attribute.
	 *
	 * @return Guild|null The guild attribute.
	 */
	public function getGuildAttribute()
	{
		return Cache::aget("guild.{$this->guild_id}");
	}
}

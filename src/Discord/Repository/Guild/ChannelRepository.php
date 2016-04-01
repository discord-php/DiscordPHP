<?php

namespace Discord\Repository\Guild;

use Discord\Parts\Channel\Channel;
use Discord\Repository\AbstractRepository;

class ChannelRepository extends AbstractRepository
{
	/**
	 * {@inheritdoc}
	 */
	protected $endpoints = [
		'all'    => 'guilds/:guild_id/channels',
		'get'    => 'channels/:id',
		'create' => 'guilds/:guild_id/channels',
		'update' => 'channels/:id',
		'delete' => 'guilds/:guild_id/channels',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $class = Channel::class;
}
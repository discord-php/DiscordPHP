<?php

namespace Discord\Repository\Channel;

use Discord\Parts\Guild\Invite;
use Discord\Repository\AbstractRepository;

class InviteRepository extends AbstractRepository
{
	/**
	 * {@inheritdoc}
	 */
	protected $endpoints = [
		'all'    => 'channels/:channel_id/invites',
		'get'    => 'invites/:code',
		'create' => 'channels/:channel_id/invites',
		'delete' => 'invites/:code',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $part = Invite::class;
}
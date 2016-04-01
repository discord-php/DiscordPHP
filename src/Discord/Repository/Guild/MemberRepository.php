<?php

namespace Discord\Repository\Guild;

use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;

class MemberRepository extends AbstractRepository
{
	/**
	 * {@inheritdoc}
	 */
	protected $endpoints = [
		'get'    => 'guild/:guild_id/members/:id',
		'update' => 'guild/:guild_id/members/:id',
		'delete' => 'guild/:guild_id/members/:id',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $class = Member::class;
}
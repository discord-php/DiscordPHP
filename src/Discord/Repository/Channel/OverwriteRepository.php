<?php

namespace Discord\Repository\Channel;

use Discord\Parts\Channel\Overwrite;
use Discord\Repository\AbstractRepository;

class OverwriteRepository extends AbstractRepository
{
	/**
	 * {@inheritdoc}
	 */
	protected $endpoints = [
		'delete' => 'channels/:channel_id/permissions/:id',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $part = Overwrite::class;
}
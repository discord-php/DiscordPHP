<?php

namespace Discord\Repository\Channel;

use Discord\Parts\Channel\Message;
use Discord\Repository\AbstractRepository;

class MessageRepository extends AbstractRepository
{
	/**
	 * {@inheritdoc}
	 */
	protected $endpoints = [
		'get'    => 'channels/:channel_id/messages/:id',
		'create' => 'channels/:channel_id/messages',
		'update' => 'channels/:channel_id/messages/:id',
		'delete' => 'channels/:channel_id/messages/:id',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $class = Message::class;
}
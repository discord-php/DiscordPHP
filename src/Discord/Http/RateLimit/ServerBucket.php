<?php

namespace Discord\Http\RateLimit;

use Discord\Http\RateLimit\AbstractBucket;
use Discord\Parts\Guild\Guild;
use React\EventLoop\LoopInterface;

class ServerBucket extends AbstractBucket
{
	/**
	 * {@inheritdoc}
	 */
	protected $uses = 5;

	/**
	 * {@inheritdoc}
	 */
	protected $time = 5;

	/**
	 * {@inheritdoc}
	 *
	 * @param Guild $guild The guild.
	 */
	public function __construct(LoopInterface $loop, Guild $guild)
	{
		$this->guild = $guild;
		$this->name = 'Guild '.$guild->name.' - '.$guild->id;

		parent::__construct($loop);
	}
}
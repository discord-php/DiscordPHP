<?php

namespace Discord\Http\RateLimit;

use Discord\Http\RateLimit\AbstractBucket;

class GlobalBucket extends AbstractBucket
{
	/**
	 * {@inheritdoc}
	 */
	protected $uses = 50;

	/**
	 * {@inheritdoc}
	 */
	protected $time = 10;
}
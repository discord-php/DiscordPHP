<?php

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * The Game part defines what game the user is playing at the moment.
 */
class Game extends Part
{
	const TYPE_PLAYING = 0;
	const TYPE_STREAMING = 1;
	
	/**
	 * {@inheritdoc}
	 */
	protected $fillable = ['name', 'url', 'type'];
}
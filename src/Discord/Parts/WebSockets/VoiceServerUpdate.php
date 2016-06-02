<?php

namespace Discord\Parts\WebSockets;

use Discord\Parts\Part;

class VoiceServerUpdate extends Part
{
	/**
	 * {@inheritdoc}
	 */
	protected $fillable = ['token', 'guild_id', 'endpoint'];
}
<?php

namespace Discord\Parts\Channel;

use Discord\Parts\Part;

class Message extends Part
{
	/**
	 * The parts fillable attributes.
	 *
	 * @var array 
	 */
	protected $fillable = ['id', 'channel_id', 'content', 'mentions', 'author', 'mention_everyone', 'timestamp', 'edited_timestamp', 'tts', 'attachments', 'embeds', 'nonce'];
}
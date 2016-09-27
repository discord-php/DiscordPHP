<?php

namespace Discord\Repository\Guild;

use Discord\Parts\Guild\Emoji;
use Discord\Repository\AbstractRepository;

/**
 * Contains emojis that belong to guilds.
 *
 * @see Discord\Parts\Guild\Emoji
 * @see Discord\Parts\Guild\Guild 
 */
class EmojiRepository extends AbstractRepository
{
	/**
	 * {@inheritdoc}
	 */
	protected $endpoints = [
		'all'    => 'guilds/:guild_id/emojis',
		'create' => 'guilds/:guild_id/emojis',
		'delete' => 'guilds/:guild_id/emojis/:id',
		'update' => 'guilds/:guild_id/emojis/:id',
	];

	/**
	 * {@inheritdoc}
	 */
	protected $part = Emoji::class;
}
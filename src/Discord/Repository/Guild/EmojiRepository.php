<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Parts\Guild\Emoji;
use Discord\Repository\AbstractRepository;

/**
 * Contains emojis that belong to guilds.
 *
 * @see \Discord\Parts\Guild\Emoji
 * @see \Discord\Parts\Guild\Guild
 */
class EmojiRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'guilds/:guild_id/emojis',
        'create' => 'guilds/:guild_id/emojis',
        'delete' => 'guilds/:guild_id/emojis/:id',
        'update' => 'guilds/:guild_id/emojis/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Emoji::class;
}

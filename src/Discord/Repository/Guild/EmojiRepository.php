<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Emoji;
use Discord\Repository\AbstractRepository;

/**
 * Contains emojis that belong to guilds.
 *
 * @see \Discord\Parts\Guild\Emoji
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Emoji|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Emoji|null first()                     Returns the first element of the collection.
 * @method Emoji|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Emoji|null find(callable $callback)    Runs a filter callback over the repository.
 */
class EmojiRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_EMOJIS,
        'get' => Endpoint::GUILD_EMOJI,
        'create' => Endpoint::GUILD_EMOJIS,
        'delete' => Endpoint::GUILD_EMOJI,
        'update' => Endpoint::GUILD_EMOJI,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Emoji::class;
}

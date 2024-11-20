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
 * Contains emojis of a guild.
 *
 * @see Emoji
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 4.0.2
 *
 * @method Emoji|null get(string $discrim, $key)
 * @method Emoji|null pull(string|int $key, $default = null)
 * @method Emoji|null first()
 * @method Emoji|null last()
 * @method Emoji|null find(callable $callback)
 */
class EmojiRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_EMOJIS,
        'get' => Endpoint::GUILD_EMOJI,
        'create' => Endpoint::GUILD_EMOJIS,
        'delete' => Endpoint::GUILD_EMOJI,
        'update' => Endpoint::GUILD_EMOJI,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Emoji::class;
}

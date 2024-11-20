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
use Discord\Parts\Guild\Sticker;
use Discord\Repository\AbstractRepository;

/**
 * Contains stickers of a guild.
 *
 * @see Sticker
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method Sticker|null get(string $discrim, $key)
 * @method Sticker|null pull(string|int $key, $default = null)
 * @method Sticker|null first()
 * @method Sticker|null last()
 * @method Sticker|null find(callable $callback)
 */
class StickerRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_STICKERS,
        'get' => Endpoint::GUILD_STICKER,
        'create' => Endpoint::GUILD_STICKERS,
        'delete' => Endpoint::GUILD_STICKER,
        'update' => Endpoint::GUILD_STICKER,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Sticker::class;
}

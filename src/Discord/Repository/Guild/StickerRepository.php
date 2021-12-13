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
 * Contains stickers that belong to guilds.
 *
 * @see \Discord\Parts\Guild\Sticker
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Sticker|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Sticker|null first()                     Returns the first element of the collection.
 * @method Sticker|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Sticker|null find(callable $callback)    Runs a filter callback over the repository.
 */
class StickerRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_STICKERS,
        'get' => Endpoint::GUILD_STICKER,
        'create' => Endpoint::GUILD_STICKERS,
        'delete' => Endpoint::GUILD_STICKER,
        'update' => Endpoint::GUILD_STICKER,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Sticker::class;
}

<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Http\Endpoint;
use Discord\Parts\StickerPack;

/**
 * Contains sticker packs of an application.
 *
 * @see StickerPack
 *
 * @since 10.47.0
 *
 * @method StickerPack|null get(string $discrim, $key)
 * @method StickerPack|null pull(string|int $key, $default = null)
 * @method StickerPack|null first()
 * @method StickerPack|null last()
 * @method StickerPack|null find(callable $callback)
 */
class StickerPackRepository extends AbstractRepository
{
    /**
     * The discriminator.
     *
     * @var string Discriminator.
     */
    protected $discrim = 'id';

    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::STICKER_PACKS,
        'get' => Endpoint::STICKER_PACK,
    ];

    /**
     * @inheritDoc
     */
    protected $class = StickerPack::class;
}

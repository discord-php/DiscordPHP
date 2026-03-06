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

namespace Discord\Parts;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\Part;
use Stringable;

/**
 * Represents a pack of standard stickers.
 *
 * @link https://docs.discord.com/developers/resources/sticker#sticker-pack-object
 *
 * @since 10.46.0
 *
 * @property string                                   $id               The id of the sticker pack.
 * @property ExCollectionInterface<Sticker>|Sticker[] $stickers         The stickers in the pack.
 * @property string                                   $name             The name of the sticker pack.
 * @property string                                   $sku_id           The SKU id of the pack.
 * @property ?string|null                             $cover_sticker_id The id of the sticker used as the pack cover, if any.
 * @property string                                   $description      The description of the sticker pack.
 * @property ?string|null                             $banner_asset_id  The id of the pack's banner asset, if any.
 */
class StickerPack extends Part implements Stringable
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'stickers',
        'name',
        'sku_id',
        'cover_sticker_id',
        'description',
        'banner_asset_id',
    ];

    /**
     * Returns the stickers attribute as a collection of Sticker objects.
     *
     * @return ExCollectionInterface<Sticker>|Sticker[]
     */
    protected function getStickersAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('stickers', Sticker::class, 'id');
    }

    public function __toString(): string
    {
        return $this->id;
    }
}

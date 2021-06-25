<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Parts\Part;

/**
 * A sticker that can be sent in a Discord message.
 *
 * @link https://discord.com/developers/docs/resources/channel#message-object-message-sticker-structure
 *
 * @property string      $id
 * @property string      $pack_id
 * @property string      $name
 * @property string      $description
 * @property array       $tags
 * @property string      $asset
 * @property string|null $preview_asset
 * @property int         $format_type
 */
class Sticker extends Part
{
    public const FORMAT_TYPE_PNG = 1;
    public const FORMAT_TYPE_APNG = 2;
    public const FORMAT_TYPE_LOTTIE = 3;

    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'pack_id', 'name', 'description', 'tags', 'asset', 'preview_asset', 'format_type'];

    /**
     * Returns the tags attribute.
     *
     * @return array
     */
    protected function getTagsAttribute(): array
    {
        if ($this->attributes['tags'] ?? null) {
            return explode(', ', $this->attributes['tags']);
        }

        return [];
    }
}

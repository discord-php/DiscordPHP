<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;

/**
 * @link https://discord.com/developers/docs/components/reference#media-gallery-media-gallery-item-structure
 *
 * @since 10.11.0
 *
 * @property UnfurledMediaItem $media       A url or attachment.
 * @property string|null       $description Alt text for the media, max 1024 characters.
 * @property bool|null         $spoiler     Whether the media should be a spoiler (blurred out). Defaults to false.
 */
class MediaGalleryItem extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'media',
        'description',
        'spoiler',
    ];

    protected function getMediaAttribute(): UnfurledMediaItem
    {
        return $this->createOf(UnfurledMediaItem::class, $this->attributes['media'], true);
    }
}

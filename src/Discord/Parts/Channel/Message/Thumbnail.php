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

/**
 * A Thumbnail is a content component that is a small image only usable as an accessory in a section. The preview comes from an url or attachment through the unfurled media item structure.
 *
 * Thumbnails are only available in messages as an accessory in a section.
 *
 * @link https://discord.com/developers/docs/components/reference#thumbnail
 *
 * @since 10.11.0
 *
 * @property int               $type        11 for thumbnail component.
 * @property string|null       $id          Optional identifier for component.
 * @property UnfurledMediaItem $media       A url or attachment.
 * @property string|null       $description Alt text for the media, max 1024 characters.
 * @property bool              $spoiler     Whether the thumbnail should be a spoiler (or blurred out). Defaults to false.
 */
class Thumbnail extends Content
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'media',
        'description',
        'spoiler',
    ];

    protected function getMediaAttribute(): UnfurledMediaItem
    {
        return $this->createOf(UnfurledMediaItem::class, $this->attributes['media'], true);
    }
}

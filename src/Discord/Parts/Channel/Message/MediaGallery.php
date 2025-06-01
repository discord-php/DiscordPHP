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

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;

/**
 * A Media Gallery is a top-level content component that allows you to display 1-10 media attachments in an organized gallery format. Each item can have optional descriptions and can be marked as spoilers.
 *
 * Media Galleries are only available in messages.
 *
 * @link https://discord.com/developers/docs/components/reference#media-gallery
 *
 * @since 10.11.0
 *
 * @property int                                      $type  12 for media gallery component.
 * @property string|null                              $id    Optional identifier for component.
 * @property ExCollectionInterface|MediaGalleryItem[] $items 1 to 10 media gallery items.
 */
class MediaGallery extends Content
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'items',
    ];

    /** @return ExCollectionInterface|MediaGalleryItem[] */
    protected function getItemsAttribute(): ExCollectionInterface
    {
        $collection = Collection::for(MediaGalleryItem::class);

        foreach ($this->attributes['items'] as $item) {
            $collection->pushItem($this->createOf(MediaGalleryItem::class, $item));
        }

        return $collection;
    }
}

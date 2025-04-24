<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

/**
 * Media gallery components allow you to group images, videos or gifs into a gallery grid.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#media-gallery
 *
 * @since 10.5.0
 */
class MediaGallery extends Component implements Contracts\ComponentV2
{
    /**
     * Array of media gallery items.
     *
     * @var MediaGalleryItem[]
     */
    private $items = [];

    /**
     * Creates a new media gallery.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Adds a media item to the gallery.
     *
     * @param MediaGalleryItem|string $item         Media gallery item or URL of the media item.
     * @param string|null             $description  Description for the media item (max 1024 characters).
     * @param bool                    $spoiler     Whether the media item is a spoiler.
     *
     * @throws \OverflowException Gallery exceeds 10 items.
     * @throws \LengthException  Description exceeds 1024 characters.
     *
     * @return $this
     */
    public function addItem(MediaGalleryItem|string $item, ?string $description = null, bool $spoiler = false): self
    {
        if (count($this->items) >= 10) {
            throw new \OverflowException('You can only have 10 items per media gallery.');
        }

        if (is_string($item)) {
            $item = MediaGalleryItem::new($item, $description, $spoiler);
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Returns all the media items in the gallery.
     *
     * @return MediaGalleryItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => Component::TYPE_MEDIA_GALLERY,
            'items' => $this->items,
        ];
    }
}

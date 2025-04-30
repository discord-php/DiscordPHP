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

use JsonSerializable;

/**
 * Represents an item in a media gallery component.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#media-gallery-object-media-gallery-item-structure
 *
 * @since 10.5.0
 */
class MediaGalleryItem implements JsonSerializable
{
    /**
     * Media item for the gallery.
     *
     * @var UnfurledMediaItem
     */
    private $media;

    /**
     * Description for the gallery item.
     *
     * @var string|null
     */
    private $description;

    /**
     * Whether the gallery item is a spoiler.
     *
     * @var bool
     */
    private $spoiler = false;

    /**
     * Creates a new media gallery item.
     *
     * @param UnfurledMediaItem|string $media       Media item or URL of the media item.
     * @param string|null              $description Description for the media item (max 1024 characters).
     * @param bool                     $spoiler     Whether the media item is a spoiler.
     *
     * @throws \LengthException Description exceeds 1024 characters.
     *
     * @return self
     */
    public static function new(UnfurledMediaItem|string $media, ?string $description = null, bool $spoiler = false): self
    {
        $item = new self();
        $item->setMedia($media);

        if ($description !== null) {
            $item->setDescription($description);
        }

        if ($spoiler) {
            $item->setSpoiler();
        }

        return $item;
    }

    /**
     * Creates a new media gallery item from an attachment.
     *
     * @param string      $filename    Name of the attachment file.
     * @param string|null $description Description for the media item (max 1024 characters).
     * @param bool        $spoiler     Whether the media item is a spoiler.
     *
     * @throws \LengthException Description exceeds 1024 characters.
     *
     * @return self
     */
    public static function fromAttachment(string $filename, ?string $description = null, bool $spoiler = false): self
    {
        return self::new(UnfurledMediaItem::fromAttachment($filename), $description, $spoiler);
    }

    /**
     * Sets the media item.
     *
     * @param UnfurledMediaItem|string $media Media item or URL of the media item.
     *
     * @return $this
     */
    public function setMedia(UnfurledMediaItem|string $media): self
    {
        if (is_string($media)) {
            $media = UnfurledMediaItem::new($media);
        }

        $this->media = $media;

        return $this;
    }

    /**
     * Returns the media item.
     *
     * @return UnfurledMediaItem
     */
    public function getMedia(): UnfurledMediaItem
    {
        return $this->media;
    }

    /**
     * Sets the description for the media item.
     *
     * @param string|null $description Description for the media item (max 1024 characters).
     *
     * @throws \LengthException Description exceeds 1024 characters.
     *
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        if ($description !== null && poly_strlen($description) > 1024) {
            throw new \LengthException('Description cannot exceed 1024 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Returns the description for the media item.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets whether the media item is a spoiler.
     *
     * @param bool $spoiler Whether the media item is a spoiler.
     *
     * @return $this
     */
    public function setSpoiler(bool $spoiler = true): self
    {
        $this->spoiler = $spoiler;

        return $this;
    }

    /**
     * Returns whether the media item is a spoiler.
     *
     * @return bool
     */
    public function isSpoiler(): bool
    {
        return $this->spoiler;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'media' => $this->media,
        ];

        if (isset($this->description)) {
            $data['description'] = $this->description;
        }

        if ($this->spoiler) {
            $data['spoiler'] = true;
        }

        return $data;
    }
}

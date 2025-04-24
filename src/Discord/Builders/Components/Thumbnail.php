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
 * Thumbnail components allow you to add a thumbnail image to a section.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#thumbnail
 *
 * @since 10.5.0
 */
class Thumbnail extends Component implements Contracts\ComponentV2
{
    /**
     * Media item for the thumbnail.
     *
     * @var UnfurledMediaItem
     */
    private $media;

    /**
     * Description for the thumbnail.
     *
     * @var string|null
     */
    private $description;

    /**
     * Whether the thumbnail is a spoiler.
     *
     * @var bool
     */
    private $spoiler = false;

    /**
     * Creates a new thumbnail.
     *
     * @param string $url URL of the media item.
     *
     * @return self
     */
    public static function new(string $url): self
    {
        $component = new self();
        $component->setMedia($url);

        return $component;
    }

    /**
     * Sets the media item for the thumbnail.
     *
     * @param UnfurledMediaItem|string $url URL of the media item.
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
     * Sets the description for the thumbnail.
     *
     * @param string|null $description Description for the thumbnail (max 1024 characters).
     *
     * @throws \LengthException Description exceeds 1024 characters.
     *
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        if ($description !== null && strlen($description) > 1024) {
            throw new \LengthException('Description cannot exceed 1024 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets whether the thumbnail is a spoiler.
     *
     * @param bool $spoiler Whether the thumbnail is a spoiler.
     *
     * @return $this
     */
    public function setSpoiler(bool $spoiler = true): self
    {
        $this->spoiler = $spoiler;

        return $this;
    }

    /**
     * Returns the media item for the thumbnail.
     *
     * @return UnfurledMediaItem
     */
    public function getMedia(): UnfurledMediaItem
    {
        return $this->media;
    }

    /**
     * Returns the description for the thumbnail.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns whether the thumbnail is a spoiler.
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
            'type' => Component::TYPE_THUMBNAIL,
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

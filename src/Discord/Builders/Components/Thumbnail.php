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

namespace Discord\Builders\Components;

use function Discord\poly_strlen;

/**
 * Thumbnail components allow you to add a thumbnail image to a section.
 *
 * @link https://discord.com/developers/docs/components/reference#thumbnail
 *
 * @since 10.5.0
 *
 * @property int               $type        11 for thumbnail component.
 * @property ?int|null         $id          Optional identifier for component.
 * @property UnfurledMediaItem $media       A url or attachment.
 * @property ?string|null      $description Alt text for the media, max 1024 characters.
 * @property ?bool|null        $spoiler     Whether the thumbnail should be a spoiler (or blurred out). Defaults to false.
 */
class Thumbnail extends Content implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_THUMBNAIL;

    /**
     * Media item for the thumbnail.
     *
     * @var UnfurledMediaItem
     */
    protected $media;

    /**
     * Description for the thumbnail.
     *
     * @var string|null
     */
    protected $description;

    /**
     * Whether the thumbnail is a spoiler.
     *
     * @var bool|null
     */
    protected $spoiler;

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
    public function setDescription(?string $description = null): self
    {
        if ($description !== null && poly_strlen($description) > 1024) {
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
    public function setSpoiler(?bool $spoiler = true): self
    {
        $this->spoiler = $spoiler;

        return $this;
    }

    /**
     * Returns the media item for the thumbnail.
     *
     * @return ?UnfurledMediaItem
     */
    public function getMedia(): ?UnfurledMediaItem
    {
        return $this->media ?? null;
    }

    /**
     * Returns the description for the thumbnail.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * Returns whether the thumbnail is a spoiler.
     *
     * @return bool
     */
    public function isSpoiler(): bool
    {
        return $this->spoiler ?? false;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'media' => $this->media,
        ];

        if (isset($this->description)) {
            $content['description'] = $this->description;
        }

        if (isset($this->spoiler)) {
            $content['spoiler'] = true;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}

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

use JsonSerializable;

/**
 * @link https://discord.com/developers/docs/components/reference#media-gallery-media-gallery-item-structure
 *
 * @since 10.11.0
 *
 * @property UnfurledMediaItem $media       A url or attachment.
 * @property string|null       $description Alt text for the media, max 1024 characters.
 * @property bool|null         $spoiler     Whether the media should be a spoiler (blurred out). Defaults to false.
 */
class MediaGalleryItem implements JsonSerializable
{
    /** @var UnfurledMediaItem */
    protected $media;

    /** @var string|null */
    protected $description = null;

    /** @var bool|null */
    protected $spoiler = null;

    public function __construct(
        UnfurledMediaItem|array $media,
        ?string $description = null,
        ?bool $spoiler = null
    ) {
        $this->setMedia($media);
        $this->description = $description;
        $this->spoiler = $spoiler;
    }

    public static function new(
        UnfurledMediaItem|array $media,
        ?string $description = null,
        ?bool $spoiler = null
    ): self
    {
        return new self($media, $description, $spoiler);
    }

    public function setMedia(UnfurledMediaItem|array $media): void
    {
        if (is_array($media)) {
            $media = new UnfurledMediaItem(
                $media['url'],
                $media['proxy_url'] ?? null,
                $media['height'] ?? null,
                $media['width'] ?? null,
                $media['content_type'] ?? null
            );
        }

        $this->media = $media;
    }

    public function jsonSerialize(): array
    {
        $data = ['media' => $this->media];

        if (isset($this->description)) {
            $data['description'] = $this->description;
        }
        if (isset($this->spoiler)) {
            $data['spoiler'] = $this->spoiler;
        }

        return $data;
    }
}

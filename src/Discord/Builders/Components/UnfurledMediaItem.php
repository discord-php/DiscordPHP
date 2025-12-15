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

namespace Discord\Builders\Components;

use Discord\Parts\Channel\Attachment;
use JsonSerializable;

/**
 * Represents an unfurled media item, which is the base for V2 components.
 * It allows you to specify an arbitrary url or attachment reference.
 *
 * @link https://discord.com/developers/docs/components/reference#unfurled-media-items
 *
 * @since 10.5.0
 *
 * @property string $url Supports arbitrary urls and attachment://<filename> references.
 */
class UnfurledMediaItem implements JsonSerializable
{
    /**
     * Source URL of media item (only supports http(s) and attachments).
     *
     * @var string
     */
    protected $url;

    /**
     * Creates a new unfurled media item.
     *
     * @param string $url URL or attachment reference of the media item.
     *
     * @return self
     */
    public static function new(string $url): self
    {
        $item = new self();
        $item->setUrl($url);

        return $item;
    }

    /**
     * Creates a new unfurled media item from an attachment.
     *
     * @param Attachment|string $filename Name of the attachment file, or null.
     *
     * @return self
     */
    public static function fromAttachment(Attachment|string $filename): self
    {
        if ($filename instanceof Attachment) {
            $filename = $filename->filename;
        }

        return self::new("attachment://{$filename}");
    }

    /**
     * Sets the URL of the media item.
     *
     * @param string $url URL or attachment reference of the media item.
     *
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Returns the URL of the media item.
     *
     * @return ?string
     */
    public function getUrl(): ?string
    {
        return $this->url ?? null;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return ['url' => $this->url];
    }
}

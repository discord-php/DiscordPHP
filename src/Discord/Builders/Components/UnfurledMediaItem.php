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

use Discord\Parts\Channel\Attachment;
use JsonSerializable;

/**
 * Represents an unfurled media item, which is the base for V2 components.
 * It allows you to specify an arbitrary url or attachment reference.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#unfurled-media-items
 *
 * @since 10.5.0
 */
class UnfurledMediaItem implements JsonSerializable
{
    /**
     * Loading states for unfurled media items.
     */
    public const LOADING_STATE_UNKNOWN = 0;
    public const LOADING_STATE_LOADING = 1;
    public const LOADING_STATE_LOADED_SUCCESS = 2;
    public const LOADING_STATE_LOADED_NOT_FOUND = 3;

    /**
     * Source URL of media item (only supports http(s) and attachments).
     *
     * @var string
     */
    private $url;

    /**
     * A proxied URL of the media item.
     *
     * @var string|null
     */
    private $proxy_url;

    /**
     * Height of media item.
     *
     * @var int|null
     */
    private $height;

    /**
     * Width of media item.
     *
     * @var int|null
     */
    private $width;

    /**
     * The media item's media type.
     *
     * @var string|null
     */
    private $content_type;

    /**
     * Loading state of the media item.
     *
     * @var int|null
     */
    private $loading_state;

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
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Sets the resolved data for the media item.
     * This is typically set by Discord after the media is resolved.
     *
     * @param array $data Resolved data from Discord.
     *
     * @return $this
     */
    public function setResolvedData(array $data): self
    {
        $this->proxy_url = $data['proxy_url'] ?? null;
        $this->width = $data['width'] ?? null;
        $this->height = $data['height'] ?? null;
        $this->content_type = $data['content_type'] ?? null;
        $this->loading_state = $data['loading_state'] ?? null;

        return $this;
    }

    /**
     * Returns whether the media item has been resolved.
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return isset($this->loading_state);
    }

    /**
     * Returns whether the media item was successfully loaded.
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loading_state === self::LOADING_STATE_LOADED_SUCCESS;
    }

    /**
     * Returns whether the media item failed to load.
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->loading_state === self::LOADING_STATE_LOADED_NOT_FOUND;
    }

    /**
     * Returns whether the media item is still loading.
     *
     * @return bool
     */
    public function isLoading(): bool
    {
        return $this->loading_state === self::LOADING_STATE_LOADING;
    }

    /**
     * Returns whether the media item's loading state is unknown.
     *
     * @return bool
     */
    public function isUnknown(): bool
    {
        return $this->loading_state === self::LOADING_STATE_UNKNOWN;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = ['url' => $this->url];

        if ($this->isResolved()) {
            $resolved = array_filter([
                'proxy_url' => $this->proxy_url ?? null,
                'width' => $this->width ?? null,
                'height' => $this->height ?? null,
                'content_type' => $this->content_type ?? null,
            ], fn ($value) => $value !== null);

            return array_merge($data, $resolved, ['loading_state' => $this->loading_state]);
        }

        return $data;
    }
}

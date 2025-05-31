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
 * A url or attachment, except in the case of File, which only supports attachment.
 *
 * @link https://discord.com/developers/docs/components/reference#unfurled-media-item-structure
 *
 * @since 10.11.0
 *
 * @property string      $url          Supports arbitrary urls and attachment://<filename> references.
 * @property string|null $proxy_url    The proxied url of the media item. This field is ignored and provided by the API as part of the response.
 * @property int|null    $height       The height of the media item. This field is ignored and provided by the API as part of the response.
 * @property int|null    $width        The width of the media item. This field is ignored and provided by the API as part of the response.
 * @property string|null $content_type The media type of the content. This field is ignored and provided by the API as part of the response.
 */
class UnfurledMediaItem implements JsonSerializable
{
    /** @var string */
    protected $url;

    /** @var string|null */
    protected $proxy_url = null;

    /** @var int|null */
    protected $height = null;

    /** @var int|null */
    protected $width = null;

    /** @var string|null */
    protected $content_type = null;

    public function __construct(
        string $url,
        ?string $proxy_url = null,
        ?int $height = null,
        ?int $width = null,
        ?string $content_type = null
    ) {
        $this->url = $url;
        $this->proxy_url = $proxy_url;
        $this->height = $height;
        $this->width = $width;
        $this->content_type = $content_type;
    }

    public static function new(
        string $url,
        ?string $proxy_url = null,
        ?int $height = null,
        ?int $width = null,
        ?string $content_type = null
    ): self {
        return new self($url, $proxy_url, $height, $width, $content_type);
    }

    public function jsonSerialize(): array
    {
        $data = ['url' => $this->url];

        if (isset($this->proxy_url)) {
            $data['proxy_url'] = $this->proxy_url;
        }
        if (isset($this->height)) {
            $data['height'] = $this->height;
        }
        if (isset($this->width)) {
            $data['width'] = $this->width;
        }
        if (isset($this->content_type)) {
            $data['content_type'] = $this->content_type;
        }

        return $data;
    }
}

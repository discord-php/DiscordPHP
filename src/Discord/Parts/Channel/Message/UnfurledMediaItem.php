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

use Discord\Parts\Part;

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
class UnfurledMediaItem extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'url',
        'proxy_url',
        'height',
        'width',
        'content_type',
    ];
}

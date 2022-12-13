<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * An image for an embed.
 *
 * @link https://discord.com/developers/docs/resources/channel#embed-object-embed-image-structure
 * @link https://discord.com/developers/docs/resources/channel#embed-object-embed-thumbnail-structure
 *
 * @since 4.0.3
 *
 * @property      string      $url       The source of the image. Must be https.
 * @property-read string|null $proxy_url A proxied version of the image.
 * @property-read int|null    $height    The height of the image.
 * @property-read int|null    $width     The width of the image.
 */
class Image extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'url',
        'proxy_url',
        'height',
        'width',
    ];
}

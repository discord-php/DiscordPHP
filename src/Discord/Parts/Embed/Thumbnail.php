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

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * The thumbnail of an embed object.
 *
 * @link https://discord.com/developers/docs/resources/message#embed-object-embed-thumbnail-structure
 *
 * @since 10.19.0
 *
 * @property ?string|null $url       Source URL of thumbnail (only supports http(s) and attachments).
 * @property ?string|null $proxy_url A proxied URL of the thumbnail.
 * @property ?int|null    $height    Height of thumbnail.
 * @property ?int|null    $width     Width of thumbnail.
 */
class Thumbnail extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'url',
        'proxy_url',
        'height',
        'width',
    ];
}

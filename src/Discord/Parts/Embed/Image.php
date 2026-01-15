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

namespace Discord\Parts\Embed;

use Discord\Parts\Channel\Attachment;
use Discord\Parts\Part;

/**
 * An image for an embed.
 *
 * @link https://discord.com/developers/docs/resources/message#embed-object-embed-image-structure
 * @link https://discord.com/developers/docs/resources/message#embed-object-embed-thumbnail-structure
 *
 * @since 4.0.3
 *
 * @property string       $url                 The source of the image. Must be https.
 * @property ?string|null $proxy_url           A proxied version of the image.
 * @property ?int|null    $height              The height of the image.
 * @property ?int|null    $width               The width of the image.
 * @property ?string|null $content_type        The image's media type.
 * @property ?string|null $placeholder         Thumbhash placeholder of the image.
 * @property ?int|null    $placeholder_version Version of the placeholder.
 * @property ?string|null $description         Description (alt text) for the image.
 * @property ?int|null    $flags               Embed media flags combined as a bitfield.
 */
class Image extends Part
{
    /** This is an animated image. */
    public const FLAG_IS_ANIMATED = Attachment::FLAG_IS_ANIMATED;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'url',
        'proxy_url',
        'height',
        'width',
        'content_type',
        'placeholder',
        'placeholder_version',
        'description',
        'flags',
    ];
}

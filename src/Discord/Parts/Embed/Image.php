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
    /** This attachment is a Clip from a stream. */
    public const FLAG_IS_CLIP = 1 << 0;
    /** This attachment is the thumbnail of a thread in a media channel, displayed in the grid but not on the message. */
    public const FLAG_IS_THUMBNAIL = 1 << 1;
    /** This attachment has been edited using the remix feature on mobile (deprecated). */
    public const FLAG_IS_REMIX = 1 << 2;
    /** This attachment was marked as a spoiler and is blurred until clicked. */
    public const FLAG_IS_SPOILER = 1 << 3;
    /** This attachment is an animated image. */
    public const FLAG_IS_ANIMATED = 1 << 5;

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

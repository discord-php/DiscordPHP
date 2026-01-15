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
 * A video for an embed.
 *
 * @link https://discord.com/developers/docs/resources/message#embed-object-embed-video-structure
 *
 * @since 4.0.3
 *
 * @property ?string|null $url                 The source of the video.
 * @property ?string|null $proxy_url           A proxied url of the video.
 * @property ?int|null    $height              The height of the video.
 * @property ?int|null    $width               The width of the video.
 * @property ?string|null $content_type        The video's media type.
 * @property ?string|null $placeholder         Thumbhash placeholder of the video.
 * @property ?int|null    $placeholder_version Version of the placeholder.
 * @property ?string|null $description         Description (alt text) for the video.
 * @property ?int|null    $flags               Embed media flags combined as a bitfield.
 */
class Video extends Part
{
    /** This video is a Clip from a stream. */
    public const FLAG_IS_CLIP = Attachment::FLAG_IS_CLIP;

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

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
    /** This is the thumbnail of a thread in a media channel, displayed in the grid but not on the message. */
    public const FLAG_IS_THUMBNAIL = Attachment::FLAG_IS_THUMBNAIL;

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

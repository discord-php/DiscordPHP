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
 * A video for an embed.
 *
 * @see https://discord.com/developers/docs/resources/channel#embed-object-embed-video-structure
 *
 * @property string|null $url       The source of the video.
 * @property string|null $proxy_url A proxied url of the video.
 * @property int|null    $height    The height of the video.
 * @property int|null    $width     The width of the video.
 */
class Video extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['url', 'proxy_url', 'height', 'width'];
}

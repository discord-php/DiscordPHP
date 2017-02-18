<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * An image for an embed.
 *
 * @property string $url       The source of the image. Must be https.
 * @property string $proxy_url A proxied version of the image.
 * @property int    $height    The height of the image.
 * @property int    $width     The width of the image.
 */
class Image extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['url', 'proxy_url', 'height', 'width'];
}

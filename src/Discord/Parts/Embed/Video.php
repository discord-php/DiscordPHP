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
 * A video for an embed.
 *
 * @property string $url    The source of the video.
 * @property int    $height The height of the video.
 * @property int    $width  The width of the video.
 */
class Video extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['url', 'height', 'width'];
}

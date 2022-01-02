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
 * @property string $url    The source of the video.
 * @property int    $height The height of the video.
 * @property int    $width  The width of the video.
 */
class Video extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['url', 'height', 'width'];
}

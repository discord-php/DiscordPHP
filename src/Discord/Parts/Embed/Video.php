<?php

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

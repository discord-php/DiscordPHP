<?php

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

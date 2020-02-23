<?php

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * The author of an embed object.
 *
 * @property string $name           The name of the author.
 * @property string $url            The URL to the author.
 * @property string $icon_url       The source of the author icon. Must be https.
 * @property string $proxy_icon_url A proxied version of the icon url.
 */
class Author extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['name', 'url', 'icon_url', 'proxy_icon_url'];
}

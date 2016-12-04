<?php

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * The footer section of an embed.
 *
 * @property string $text           Footer text.
 * @property string $icon_url       URL of an icon for the footer. Must be https.
 * @property string $proxy_icon_url Proxied version of the icon URL.
 */
class Footer extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['text', 'icon_url', 'proxy_icon_url'];
}

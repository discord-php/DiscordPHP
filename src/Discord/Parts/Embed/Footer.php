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
 * The footer section of an embed.
 *
 * @property string $text           Footer text.
 * @property string $icon_url       URL of an icon for the footer. Must be https.
 * @property string $proxy_icon_url Proxied version of the icon URL.
 */
class Footer extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['text', 'icon_url', 'proxy_icon_url'];
}

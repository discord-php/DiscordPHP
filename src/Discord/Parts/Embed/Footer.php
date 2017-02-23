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

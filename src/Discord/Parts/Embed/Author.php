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

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
 * The author of an embed object.
 *
 * @see https://discord.com/developers/docs/resources/channel#embed-object-embed-author-structure
 *
 * @property string      $name           The name of the author.
 * @property string|null $url            The URL to the author.
 * @property string|null $icon_url       The source of the author icon. Must be https.
 * @property string|null $proxy_icon_url A proxied version of the icon url.
 */
class Author extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['name', 'url', 'icon_url', 'proxy_icon_url'];
}

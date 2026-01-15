<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * The provider of an embed object.
 *
 * @link https://discord.com/developers/docs/resources/message#embed-object-embed-provider-structure
 *
 * @since 10.19.0
 *
 * @property ?string|null $name The name of the provider.
 * @property ?string|null $url  The URL of the provider.
 */
class Provider extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'name',
        'url',
    ];
}

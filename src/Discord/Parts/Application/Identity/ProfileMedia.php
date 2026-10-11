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

namespace Discord\Parts\Application\Identity;

use Discord\Parts\Part;

/**
 * An image URL reachable by Discord's media unfurler.
 *
 * @since 10.59.0
 * @link https://docs.discord.com/developers/resources/application-identity-profile#media-object
 * @property string|null $url Media asset URL.
 */
class ProfileMedia extends Part
{
    protected $fillable = ['url'];

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->getRawAttributes();
    }
}
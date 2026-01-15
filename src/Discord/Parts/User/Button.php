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

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * Custom buttons shown in the Rich Presence (max 2).
 *
 * When received over the gateway, the buttons field is an array of strings, which are the button labels.
 * Bots cannot access a user's activity button URLs.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#activity-object-activity-buttons
 *
 * @since 10.24.0
 *
 * @property string|null $label Text shown on the button (1-32 characters).
 * @property string|null $url   URL opened when clicking the button (1-512 characters).
 */
class Button extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'label',
        'url',
    ];
}

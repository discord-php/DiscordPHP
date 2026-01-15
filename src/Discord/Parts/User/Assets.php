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
 * Images for the presence and their hover texts.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#activity-object-activity-assets
 * @link https://discord.com/developers/docs/events/gateway-events#activity-object-activity-asset-image
 *
 * @since 10.19.0
 *
 * @property ?string|null $large_image       See Activity Asset Image.
 * @property ?string|null $large_text        Text displayed when hovering over the large image of the activity.
 * @property ?string|null $large_url         URL that is opened when clicking on the large image.
 * @property ?string|null $small_image       See Activity Asset Image.
 * @property ?string|null $small_text        Text displayed when hovering over the small image of the activity.
 * @property ?string|null $small_url         URL that is opened when clicking on the small image.
 * @property ?string|null $nvite_cover_image Displayed as a banner on a Game Invite.
 */
class Assets extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'large_image',
        'large_text',
        'large_url',
        'small_image',
        'small_text',
        'small_url',
        'invite_cover_image',
    ];
}

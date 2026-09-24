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

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;

/**
 * The game stats an application publishes for a user's external identity, shown in game stats widgets.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/resources/application-identity-profile#application-identity-profile-object
 *
 * @property ?string|null $username The user's username in the external system.
 * @property ?array|null  $metadata Arbitrary game-defined data; not consumed by Discord.
 * @property ?array|null  $data     The profile data: `primary` pre-configured stats and `dynamic` custom fields.
 */
class ApplicationIdentityProfile extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'username',
        'metadata',
        'data',
    ];
}

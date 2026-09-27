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
use Discord\Parts\User\User;

/**
 * A link between a Discord user and their account in an external system, recorded for an application.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/resources/application-identity-profile#application-identity-object
 *
 * @property      string       $user_id                 The Discord user ID.
 * @property      string       $provider_type           The external account provider type.
 * @property      ?string|null $provider_id             Provider-specific identifier used to disambiguate identities.
 * @property      string       $provider_issued_user_id The user's ID in the external system.
 * @property-read User|null    $user                    The Discord user, when it is cached.
 */
class ApplicationIdentity extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'user_id',
        'provider_type',
        'provider_id',
        'provider_issued_user_id',
    ];

    /**
     * Gets the user attribute.
     *
     * @return User|null The Discord user, when it is cached.
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }
}

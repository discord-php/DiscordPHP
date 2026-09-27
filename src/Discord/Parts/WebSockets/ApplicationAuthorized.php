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

namespace Discord\Parts\WebSockets;

use Discord\Parts\Guild\Guild;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Sent when a user adds the application to a server or to their account.
 *
 * Arrives as a webhook event, not over the gateway.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/events/webhook-events#application-authorized
 *
 * @property      int|null   $integration_type Where the application was installed: {@see Application::INTEGRATION_TYPE_GUILD_INSTALL} or {@see Application::INTEGRATION_TYPE_USER_INSTALL}.
 * @property      User       $user             The user who authorized the application.
 * @property      string[]   $scopes           The scopes the user authorized.
 * @property-read Guild|null $guild            The server the application was added to, when it was added to one.
 */
class ApplicationAuthorized extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'integration_type',
        'user',
        'scopes',
        'guild',
    ];

    /**
     * Gets the user attribute.
     *
     * @return User|null The user who authorized the application.
     */
    protected function getUserAttribute(): ?User
    {
        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The server the application was added to, preferring the cached one.
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->attributes['guild']->id ?? null)
            ?? $this->attributePartHelper('guild', Guild::class);
    }
}

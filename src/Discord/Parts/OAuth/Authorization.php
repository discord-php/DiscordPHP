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

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * What a user has authorised an application to do with their OAuth2 token: the application, the scopes
 * granted, when the token expires, and the user, when the token has the `identify` scope.
 *
 * @link https://docs.discord.com/developers/topics/oauth2#get-current-authorization-information
 *
 * @since 10.60.0
 *
 * @property      Application $application The application the token was granted to.
 * @property      string[]    $scopes      The scopes the user granted.
 * @property      Carbon      $expires     When the token expires.
 * @property-read ?User|null  $user        The user who granted it, when the token has the `identify` scope.
 */
class Authorization extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'application',
        'scopes',
        'expires',
        'user',
    ];

    /**
     * Returns the application the token was granted to.
     *
     * @return Application|null
     */
    protected function getApplicationAttribute(): ?Application
    {
        return $this->attributePartHelper('application', Application::class);
    }

    /**
     * Returns when the token expires.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getExpiresAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('expires');
    }

    /**
     * Returns the user who granted the token, when it has the `identify` scope.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->attributePartHelper('user', User::class);
    }
}

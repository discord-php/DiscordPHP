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
 * A user as OpenID Connect describes them, from Discord's user info endpoint. It needs a token with the
 * `openid` scope; the email claims also need the `email` scope.
 *
 * Discord describes the endpoint in its OpenAPI description rather than in its documentation.
 *
 * @link https://openid.net/specs/openid-connect-core-1_0.html#UserInfo
 *
 * @since 10.60.0
 *
 * @property string       $sub                The user's ID.
 * @property ?string|null $email              Their email address, with the `email` scope.
 * @property ?bool|null   $email_verified     Whether they have verified that address, with the `email` scope.
 * @property ?string|null $preferred_username Their username.
 * @property ?string|null $nickname           Their display name.
 * @property ?string|null $picture            The URL of their avatar.
 * @property ?string|null $locale             Their chosen language.
 */
class UserInfo extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'sub',
        'email',
        'email_verified',
        'preferred_username',
        'nickname',
        'picture',
        'locale',
    ];
}

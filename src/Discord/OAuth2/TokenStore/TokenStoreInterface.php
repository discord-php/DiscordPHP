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

namespace Discord\OAuth2\TokenStore;

use Discord\OAuth2\AccessToken;
use React\Promise\PromiseInterface;

/**
 * Where users' OAuth2 tokens are kept between restarts.
 *
 * Deliberately separate from the part cache, which is built to lose data: a
 * TTL, a sweep or an eviction policy dropping a token would sign that user out,
 * and Discord replaces the refresh token every time it is used, so a lost one
 * cannot be recovered. Keep tokens somewhere durable, and treat them as the
 * credentials they are.
 *
 * @since 10.59.0
 */
interface TokenStoreInterface
{
    /**
     * @param string $key What the token was stored under.
     *
     * @return PromiseInterface<?AccessToken> The token, or null if none is stored.
     */
    public function get(string $key): PromiseInterface;

    /**
     * @param string      $key   What to store the token under.
     * @param AccessToken $token The token.
     *
     * @return PromiseInterface<bool>
     */
    public function set(string $key, AccessToken $token): PromiseInterface;

    /**
     * @param string $key What the token was stored under.
     *
     * @return PromiseInterface<bool>
     */
    public function delete(string $key): PromiseInterface;
}

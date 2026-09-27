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

use function React\Promise\resolve;

/**
 * Keeps tokens in memory, for as long as the process runs.
 *
 * The default. Every user has to authorize again after a restart, so give
 * Discord a durable store — see {@see CacheTokenStore} — before relying on it.
 *
 * @since 10.59.0
 */
final class ArrayTokenStore implements TokenStoreInterface
{
    /** @var array<string, AccessToken> */
    private array $tokens = [];

    public function get(string $key): PromiseInterface
    {
        return resolve($this->tokens[$key] ?? null);
    }

    public function set(string $key, AccessToken $token): PromiseInterface
    {
        $this->tokens[$key] = $token;

        return resolve(true);
    }

    public function delete(string $key): PromiseInterface
    {
        unset($this->tokens[$key]);

        return resolve(true);
    }
}

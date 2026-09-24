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
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use React\Cache\CacheInterface as ReactCacheInterface;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Keeps tokens in a ReactPHP or PSR-16 cache — Redis, for instance.
 *
 * Tokens are stored with no expiry of their own. Give this a cache that will
 * not evict them: a dedicated Redis database with no `maxmemory` eviction, not
 * the one the part cache shares.
 *
 * @since 10.59.0
 */
final class CacheTokenStore implements TokenStoreInterface
{
    /**
     * @param ReactCacheInterface|PsrCacheInterface $cache  Where to keep the tokens.
     * @param string                                $prefix Prepended to every key.
     */
    public function __construct(
        private ReactCacheInterface|PsrCacheInterface $cache,
        private string $prefix = 'discordphp.oauth2.token.',
    ) {
    }

    public function get(string $key): PromiseInterface
    {
        return $this->call(fn () => $this->cache->get($this->key($key)))
            ->then(static function ($stored): ?AccessToken {
                if (! is_string($stored) || '' === $stored) {
                    return null;
                }

                $decoded = json_decode($stored, true);

                return is_array($decoded) ? AccessToken::fromArray($decoded) : null;
            });
    }

    public function set(string $key, AccessToken $token): PromiseInterface
    {
        return $this->call(fn () => $this->cache->set($this->key($key), json_encode($token)));
    }

    public function delete(string $key): PromiseInterface
    {
        return $this->call(fn () => $this->cache->delete($this->key($key)));
    }

    /**
     * Encoded, because PSR-16 reserves `{}()/\@:` in keys and a store key may well contain `:`.
     */
    private function key(string $key): string
    {
        return $this->prefix.rawurlencode($key);
    }

    /**
     * Runs a cache call, which a ReactPHP cache answers with a promise and a PSR-16 cache answers directly, or by throwing.
     *
     * @return PromiseInterface
     */
    private function call(callable $call): PromiseInterface
    {
        try {
            $result = $call();
        } catch (\Throwable $e) {
            return reject($e);
        }

        return $result instanceof PromiseInterface ? $result : resolve($result);
    }
}

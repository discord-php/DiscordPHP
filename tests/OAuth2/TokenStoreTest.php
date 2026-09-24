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

use Discord\Discord;
use Discord\OAuth2\AccessToken;
use Discord\OAuth2\TokenStore\ArrayTokenStore;
use Discord\OAuth2\TokenStore\CacheTokenStore;
use Discord\OAuth2\TokenStore\TokenStoreInterface;
use Psr\SimpleCache\CacheInterface;
use React\Cache\ArrayCache;

final class TokenStoreTest extends DiscordTestCase
{
    public function testTheArrayStoreKeepsATokenUntilDeleted()
    {
        return $this->roundTrip(new ArrayTokenStore());
    }

    public function testAReactCacheKeepsATokenUntilDeleted()
    {
        return $this->roundTrip(new CacheTokenStore(new ArrayCache()));
    }

    public function testAPsr16CacheKeepsATokenUntilDeleted()
    {
        return $this->roundTrip(new CacheTokenStore($this->psr16()));
    }

    public function testKeysPsr16ReservesAreEncoded()
    {
        return wait(function (Discord $discord, $resolve) {
            $cache = $this->psr16();
            $store = new CacheTokenStore($cache);

            $store->set('provisional:player@1', new AccessToken('abc'))
                ->then(function () use ($cache) {
                    $this->assertSame(['discordphp.oauth2.token.provisional%3Aplayer%401'], array_keys($cache->items));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAFailingPsr16CacheRejectsRatherThanThrows()
    {
        return wait(function (Discord $discord, $resolve) {
            $cache = $this->psr16();
            $cache->fail = true;

            (new CacheTokenStore($cache))->get('key')
                ->then(
                    fn () => $this->fail('a failing cache should reject'),
                    fn (\Throwable $e) => $this->assertSame('cache is down', $e->getMessage())
                )
                ->then($resolve, $resolve);
        });
    }

    private function roundTrip(TokenStoreInterface $store)
    {
        return wait(function (Discord $discord, $resolve) use ($store) {
            $token = new AccessToken('abc', 'Bearer', 'def', 1000, ['identify']);

            $store->get('player-1')
                ->then(function ($missing) use ($store, $token) {
                    $this->assertNull($missing);

                    return $store->set('player-1', $token);
                })
                ->then(fn () => $store->get('player-1'))
                ->then(function ($stored) use ($store, $token) {
                    $this->assertEquals($token, $stored);

                    return $store->delete('player-1');
                })
                ->then(fn () => $store->get('player-1'))
                ->then(fn ($deleted) => $this->assertNull($deleted))
                ->then($resolve, $resolve);
        });
    }

    /**
     * A PSR-16 cache that keeps items in an array, and can be made to fail.
     */
    private function psr16(): CacheInterface
    {
        return new class () implements CacheInterface {
            public array $items = [];

            public bool $fail = false;

            public function get(string $key, mixed $default = null): mixed
            {
                if ($this->fail) {
                    throw new \RuntimeException('cache is down');
                }

                return $this->items[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
            {
                $this->items[$key] = $value;

                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->items[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->items = [];

                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                return [];
            }

            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
            {
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return isset($this->items[$key]);
            }
        };
    }
}

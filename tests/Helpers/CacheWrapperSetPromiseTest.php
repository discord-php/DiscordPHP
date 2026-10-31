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

use Discord\Helpers\CacheConfig;
use Discord\Helpers\CacheWrapper;
use Discord\Parts\User\User;
use PHPUnit\Framework\TestCase;
use React\Cache\ArrayCache;
use React\Cache\CacheInterface as ReactCache;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

final class CacheWrapperSetPromiseTest extends TestCase
{
    public function testSetSerializationFailureRejectsWithoutWriting(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $old = $client->getFactory()->part($class, ['id' => '123'], true);
        $items = ['123' => $old];
        $part = $client->getFactory()->part($class, ['id' => '123'], true);
        $part->scriptData = static function () {
        };
        $backend = $this->createMock(ReactCache::class);
        $backend->expects($this->never())->method('set');
        $cache = new CacheWrapper($client, new CacheConfig($backend), $items, $class, []);

        $this->assertSerializationRejected($cache->set('123', $part));
        $this->assertSame(['123' => $old], $items);
    }

    public function testSetMultipleSerializationFailureRejectsWithoutWritingFailedItem(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $old = $client->getFactory()->part($class, ['id' => '456'], true);
        $items = ['456' => $old];
        $first = $client->getFactory()->part($class, ['id' => '123'], true);
        $failed = $client->getFactory()->part($class, ['id' => '456'], true);
        $failed->scriptData = static function () {
        };
        $backend = $this->createMock(ReactCache::class);
        $backend->expects($this->once())->method('set')
            ->with('User.123', $this->isString(), null)
            ->willReturn(resolve(true));
        $cache = new CacheWrapper($client, new CacheConfig($backend), $items, $class, []);

        $this->assertSerializationRejected($cache->setMultiple(['123' => $first, '456' => $failed]));
        $this->assertSame($first, $items['123']);
        $this->assertSame($old, $items['456']);
    }

    public function testBackendFailureStillRejectsWithoutReplacingLocalValue(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $old = $client->getFactory()->part($class, ['id' => '123'], true);
        $items = ['123' => $old];
        $part = $client->getFactory()->part($class, ['id' => '123'], true);
        $exception = new RuntimeException('backend failed');
        $backend = $this->createMock(ReactCache::class);
        $backend->expects($this->once())->method('set')->willThrowException($exception);
        $cache = new CacheWrapper($client, new CacheConfig($backend), $items, $class, []);
        $rejection = null;
        $promise = $cache->set('123', $part);
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->then(null, function ($error) use (&$rejection) {
            $rejection = $error;
        });

        $this->assertSame($exception, $rejection);
        $this->assertSame(['123' => $old], $items);
    }

    // Batch writes are not atomic: entries after a rejection are still attempted.
    public function testSetMultipleContinuesAfterSerializationFailure(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $old = $client->getFactory()->part($class, ['id' => '456'], true);
        $items = ['456' => $old];
        $first = $client->getFactory()->part($class, ['id' => '123'], true);
        $failed = $client->getFactory()->part($class, ['id' => '456'], true);
        $last = $client->getFactory()->part($class, ['id' => '789'], true);
        $failed->scriptData = static function () {
        };
        $stored = ['User.456' => 'old backend value'];
        $attempted = [];
        $backend = $this->createMock(ReactCache::class);
        $backend->expects($this->exactly(2))->method('set')
            ->willReturnCallback(function ($key, $value) use (&$attempted, &$stored) {
                $attempted[] = $key;
                $stored[$key] = $value;

                return resolve(true);
            });
        $cache = new CacheWrapper($client, new CacheConfig($backend), $items, $class, []);

        $this->assertSerializationRejected($cache->setMultiple(['123' => $first, '456' => $failed, '789' => $last]));
        $this->assertSame(['User.123', 'User.789'], $attempted);
        $this->assertIsString($stored['User.123']);
        $this->assertSame('old backend value', $stored['User.456']);
        $this->assertIsString($stored['User.789']);
        $this->assertSame($first, $items['123']);
        $this->assertSame($old, $items['456']);
        $this->assertSame($last, $items['789']);
    }

    public function testArrayCacheCanStorePartWithoutSerializingIt(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $items = [];
        $part = $client->getFactory()->part($class, ['id' => '123'], true);
        $part->scriptData = static function () {
        };
        $cache = new CacheWrapper($client, new CacheConfig(new ArrayCache()), $items, $class, []);
        $result = null;
        $promise = $cache->set('123', $part);
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->then(function ($value) use (&$result) {
            $result = $value;
        });

        $this->assertTrue($result);
        $this->assertSame($part, $items['123']);
    }

    public function testSetMultipleKeepsSuccessfulAggregateResults(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $items = [];
        $first = $client->getFactory()->part($class, ['id' => '123'], true);
        $second = $client->getFactory()->part($class, ['id' => '456'], true);
        $cache = new CacheWrapper($client, new CacheConfig(new ArrayCache()), $items, $class, []);
        $results = null;
        $promise = $cache->setMultiple(['123' => $first, '456' => $second]);
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->then(function ($value) use (&$results) {
            $results = $value;
        });

        $this->assertSame(['123' => true, '456' => true], $results);
        $this->assertSame(['123' => $first, '456' => $second], $items);
    }

    private function assertSerializationRejected($promise): void
    {
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $rejection = null;
        $promise->then(null, function ($error) use (&$rejection) {
            $rejection = $error;
        });
        $this->assertInstanceOf(Throwable::class, $rejection);
        $this->assertStringContainsString('Closure', $rejection->getMessage());
    }
}

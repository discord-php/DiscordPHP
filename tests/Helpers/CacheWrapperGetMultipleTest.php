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
use Psr\SimpleCache\CacheInterface as PsrCache;
use React\Cache\CacheInterface as ReactCache;
use React\Promise\PromiseInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

use function React\Promise\reject;

class CacheWrapperGetMultipleTest extends TestCase
{
    public function testPsrDecodeExceptionRejectsPromise(): void
    {
        $backend = new Psr16Cache(new ArrayAdapter());
        $backend->set('User.123', new stdClass());
        $items = [];
        $class = User::class;
        $exception = new RuntimeException('decode failed');
        $cache = new class(getMockDiscord(), new CacheConfig($backend), $items, $class, [], $exception) extends CacheWrapper {
            public function __construct($discord, $config, &$items, &$class, $vars, private RuntimeException $exception)
            {
                parent::__construct($discord, $config, $items, $class, $vars);
            }

            public function unserializer($value)
            {
                throw $this->exception;
            }
        };

        $this->assertRejectedWith($exception, $cache->getMultiple(['123']));
    }

    public function testPsrBackendExceptionRejectsPromise(): void
    {
        $exception = new RuntimeException('cache read failed');
        $backend = $this->createStub(PsrCache::class);
        $backend->method('getMultiple')->willThrowException($exception);
        $items = [];
        $class = User::class;
        $cache = new CacheWrapper(getMockDiscord(), new CacheConfig($backend), $items, $class, []);

        $this->assertRejectedWith($exception, $cache->getMultiple(['123']));
    }

    public function testAsyncBackendExceptionRejectsPromise(): void
    {
        $exception = new RuntimeException('async cache read failed');
        $backend = $this->createStub(ReactCache::class);
        $backend->method('getMultiple')->willReturnCallback(fn () => reject($exception));
        $items = [];
        $class = User::class;
        $cache = new CacheWrapper(getMockDiscord(), new CacheConfig($backend), $items, $class, []);

        $this->assertRejectedWith($exception, $cache->getMultiple(['123']));
    }

    public function testPsrMultipleResultsKeepPrefixedKeysAndMissingValues(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $items = [];
        $cache = new CacheWrapper($client, new CacheConfig(new Psr16Cache(new ArrayAdapter())), $items, $class, []);
        $first = $client->getFactory()->part($class, ['id' => '123', 'username' => 'first'], true);
        $second = $client->getFactory()->part($class, ['id' => '456', 'username' => 'second'], true);
        $cache->set('123', $first);
        $cache->set('456', $second);
        $items = ['User.missing' => $first];
        $values = null;
        $promise = $cache->getMultiple(['123', 'missing', '456']);
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->then(function ($result) use (&$values) {
            $values = $result;
        });

        $this->assertSame(['User.123', 'User.missing', 'User.456'], array_keys($values));
        $this->assertInstanceOf(User::class, $values['User.123']);
        $this->assertNotSame($first, $values['User.123']);
        $this->assertSame('123', $values['User.123']->id);
        $this->assertSame('first', $values['User.123']->username);
        $this->assertNull($values['User.missing']);
        $this->assertInstanceOf(User::class, $values['User.456']);
        $this->assertNotSame($second, $values['User.456']);
        $this->assertSame('456', $values['User.456']->id);
        $this->assertSame('second', $values['User.456']->username);
        $this->assertSame($values['User.123'], $items['User.123']);
        $this->assertSame($values['User.456'], $items['User.456']);
        $this->assertArrayNotHasKey('User.missing', $items);
    }

    private function assertRejectedWith(RuntimeException $exception, $promise): void
    {
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $rejection = null;
        $promise->then(null, function ($error) use (&$rejection) {
            $rejection = $error;
        });
        $this->assertSame($exception, $rejection);
    }
}

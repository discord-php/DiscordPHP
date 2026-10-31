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
use React\Cache\ArrayCache;
use React\Cache\CacheInterface as ReactCache;
use React\Promise\PromiseInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

use function React\Promise\resolve;

class CacheWrapperPsrTest extends TestCase
{
    public function testSymfonyPsr16RoundTrip(): void
    {
        $this->assertRoundTrip(new Psr16Cache(new ArrayAdapter()));
    }

    public function testReactArrayCacheRoundTrip(): void
    {
        $this->assertRoundTrip(new ArrayCache());
    }

    public function testSerializedReactCacheRoundTripsWithAndWithoutCompression(): void
    {
        foreach ([false, true] as $compress) {
            $stored = [];
            $backend = $this->createStub(ReactCache::class);
            $backend->method('set')->willReturnCallback(function ($key, $value) use (&$stored) {
                $stored[$key] = $value;

                return resolve(true);
            });
            $backend->method('get')->willReturnCallback(function ($key, $default = null) use (&$stored) {
                return resolve($stored[$key] ?? $default);
            });
            $this->assertRoundTrip($backend, $compress);
            $this->assertIsString($stored['User.123']);
        }
    }

    public function testMissingPsrValueClearsLocalReference(): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $items = ['123' => $client->getFactory()->part($class, ['id' => '123'], true)];
        $cache = new CacheWrapper($client, new CacheConfig(new Psr16Cache(new ArrayAdapter())), $items, $class, []);
        $value = 'not resolved';
        $cache->get('123')->then(function ($result) use (&$value) {
            $value = $result;
        });

        $this->assertNull($value);
        $this->assertArrayNotHasKey('123', $items);
    }

    public function testPsrBackendExceptionRejectsPromise(): void
    {
        $exception = new RuntimeException('cache read failed');
        $backend = $this->createStub(PsrCache::class);
        $backend->method('get')->willThrowException($exception);
        $items = [];
        $class = User::class;
        $cache = new CacheWrapper(getMockDiscord(), new CacheConfig($backend), $items, $class, []);
        $rejection = null;
        $promise = $cache->get('123');
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->then(null, function ($error) use (&$rejection) {
            $rejection = $error;
        });
        $this->assertSame($exception, $rejection);
    }

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
        $rejection = null;
        $promise = $cache->get('123');
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->then(null, function ($error) use (&$rejection) {
            $rejection = $error;
        });
        $this->assertSame($exception, $rejection);
    }

    private function assertRoundTrip($backend, bool $compress = false): void
    {
        $client = getMockDiscord();
        $class = User::class;
        $items = [];
        $cache = new CacheWrapper($client, new CacheConfig($backend, $compress), $items, $class, []);
        $part = $client->getFactory()->part($class, [
            'id' => '123',
            'username' => 'example',
            'bot' => true,
            'flags' => 42,
            'locale' => null,
        ], true);
        $stored = false;
        $cache->set('123', $part)->then(function ($result) use (&$stored) {
            $stored = $result;
        });
        $this->assertTrue($stored);
        $items = [];
        $value = null;
        $cache->get('123')->then(function ($result) use (&$value) {
            $value = $result;
        });

        $this->assertInstanceOf(User::class, $value);
        $this->assertNotSame($part, $value);
        $this->assertSame('123', $value->id);
        $this->assertSame('example', $value->username);
        $this->assertSame(true, $value->bot);
        $this->assertSame(42, $value->flags);
        $this->assertArrayHasKey('locale', $value->getRawAttributes());
        $this->assertNull($value->getRawAttributes()['locale']);
        $this->assertSame($value, $items['123']);
    }
}

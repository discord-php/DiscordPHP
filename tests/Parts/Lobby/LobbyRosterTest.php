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
use Discord\OAuth2\Session;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Member;
use React\Http\Message\Response;
use Discord\Helpers\CacheConfig;
use Discord\Helpers\CacheWrapper;
use Discord\MessageCommandClient;
use Psr\Log\NullLogger;
use React\Cache\ArrayCache;
use React\Promise\Deferred;

final class LobbyRosterTest extends DiscordTestCase
{
    public function testSingleMutationsUpdateCachedAndPassedLobbies()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn ($method) => $method === 'DELETE' ? null : ['id' => '5', 'metadata' => null, 'flags' => 1, 'additional_name' => null]);
            $mock->getHttpClient()->setDriver($driver);
            $cached = $this->lobby($mock);
            $passed = $this->lobby($mock);
            $mock->lobbies->cache->set('1', $cached)
                ->then(fn () => $mock->lobbies->addMember($passed, '5', ['metadata' => null, 'additional_name' => null]))
                ->then(function (Member $member) use ($mock, $cached, $passed, $driver) {
                    foreach ([$cached, $passed] as $lobby) {
                        $this->assertSame($member, $lobby->members->get('id', '5'));
                        $this->assertNull($lobby->members->get('id', '5')->metadata);
                        $this->assertNull($lobby->members->get('id', '5')->additional_name);
                        $this->assertNotNull($lobby->members->get('id', '6'));
                    }
                    $this->assertSame(['metadata' => null, 'additional_name' => null], $driver->requests[0]['content']);

                    return $mock->lobbies->removeMember($passed, '5');
                })
                ->then(function ($result) use ($mock, $cached, $passed) {
                    $this->assertNull($result);
                    foreach ([$cached, $passed] as $lobby) {
                        $this->assertNull($lobby->members->get('id', '5'));
                        $this->assertNotNull($lobby->members->get('id', '6'));
                    }

                    return $mock->lobbies->cacheGet('1')->then(fn ($lobby) => $this->assertSame($cached, $lobby));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testBulkUsesOnlyReturnedUpsertsAndRequestedRemovals()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => [['id' => '5', 'flags' => 1, 'additional_name' => 'kept'], ['id' => '7', 'metadata' => null]]);
            $mock->getHttpClient()->setDriver($driver);
            $lobby = $this->lobby($mock);
            $payload = [
                ['id' => '5', 'flags' => 1],
                ['id' => '6', 'remove_member' => true],
                $mock->getFactory()->part(Member::class, ['id' => '7', 'metadata' => null]),
                ['id' => '8', 'additional_name' => 'silently omitted'],
            ];
            $mock->lobbies->cache->set('1', $lobby)
                ->then(fn () => $mock->lobbies->bulkUpdateMembers('1', $payload))
                ->then(function ($members) use ($lobby, $driver) {
                    $this->assertCount(2, $members);
                    $this->assertSame($members->get('id', '5'), $lobby->members->get('id', '5'));
                    $this->assertSame('kept', $lobby->members->get('id', '5')->additional_name);
                    $this->assertNull($lobby->members->get('id', '6'));
                    $this->assertSame($members->get('id', '7'), $lobby->members->get('id', '7'));
                    $this->assertNull($lobby->members->get('id', '8'));
                    $this->assertSame(['id' => '5', 'flags' => 1], $driver->requests[0]['content'][0]);
                    $this->assertSame(['id' => '7', 'metadata' => null], $driver->requests[0]['content'][2]);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testUncachedIdsDoNotCreatePartialLobbiesButPassedObjectsUpdate()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn ($method) => $method === 'DELETE' ? null : ($method === 'POST' ? [['id' => '7']] : ['id' => '7']));
            $mock->getHttpClient()->setDriver($driver);
            $passed = $this->lobby($mock);
            $mock->lobbies->addMember('1', '7')
                ->then(fn () => $mock->lobbies->removeMember('1', '5'))
                ->then(fn () => $mock->lobbies->bulkUpdateMembers($passed, [['id' => '7'], ['id' => '6', 'remove_member' => true]]))
                ->then(function () use ($mock, $passed, $driver) {
                    $this->assertNotNull($passed->members->get('id', '7'));
                    $this->assertNull($passed->members->get('id', '6'));
                    $this->assertNotNull($passed->members->get('id', '5'));
                    $this->assertCount(3, $driver->requests, 'Roster operations must not fetch the lobby.');

                    return $mock->lobbies->cacheGet('1')->then(fn ($cached) => $this->assertNull($cached));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testFailedRequestsLeaveCachedAndPassedRostersUnchanged()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn () => new Response(404, ['Content-Type' => 'application/json'], '{"code":10013,"message":"Unknown User"}')));
            $cached = $this->lobby($mock);
            $passed = $this->lobby($mock);
            $before = $cached->getRawAttributes();
            $chain = $mock->lobbies->cache->set('1', $cached);
            foreach (['add', 'remove', 'bulk'] as $operation) {
                $chain = $chain->then(function () use ($mock, $passed, $operation) {
                    return match ($operation) {
                        'add' => $mock->lobbies->addMember($passed, '5', ['metadata' => null]),
                        'remove' => $mock->lobbies->removeMember($passed, '5'),
                        'bulk' => $mock->lobbies->bulkUpdateMembers($passed, [['id' => '5', 'remove_member' => true], ['id' => '8']]),
                    };
                })->then(fn () => $this->fail('The request should reject.'), function ($error) use ($cached, $passed, $before) {
                    $this->assertInstanceOf(\Throwable::class, $error);
                    $this->assertSame($before, $cached->getRawAttributes());
                    $this->assertSame($before, $passed->getRawAttributes());
                });
            }
            $chain->then($resolve, $resolve);
        });
    }

    public function testSingleAddAndRemovalOnlyBulkPreserveUntouchedMembers()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn ($method) => $method === 'PUT' ? ['id' => '7', 'metadata' => null] : []));
            $lobby = $this->lobby($mock);
            $mock->lobbies->cache->set('1', $lobby)
                ->then(fn () => $mock->lobbies->addMember('1', '7'))
                ->then(function (Member $member) use ($mock, $lobby) {
                    $this->assertCount(3, $lobby->members);
                    $this->assertSame($member, $lobby->members->get('id', '7'));

                    return $mock->lobbies->bulkUpdateMembers('1', [['id' => '7', 'remove_member' => true], ['id' => '8'], ['id' => '5', 'additional_name' => 'omitted update']]);
                })
                ->then(function ($members) use ($lobby) {
                    $this->assertCount(0, $members);
                    $this->assertCount(2, $lobby->members);
                    $this->assertNull($lobby->members->get('id', '7'));
                    $this->assertNull($lobby->members->get('id', '8'));
                    $this->assertSame('kept', $lobby->members->get('id', '5')->additional_name);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testSessionCachesAreIsolatedFromEachOtherAndBotWithSharedBackend()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = new MessageCommandClient(['token' => '', 'logger' => new NullLogger(), 'cache' => new CacheConfig(new ArrayCache())]);
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn ($method) => $method === 'DELETE' ? null : ['id' => '7']));
            $one = new Session($mock, new AccessToken('one'));
            $two = new Session($mock, new AccessToken('two'));
            $botLobby = $this->lobby($mock);
            $oneLobby = $this->lobby($mock);
            $twoLobby = $this->lobby($mock);
            $mock->lobbies->cache->set('1', $botLobby)
                ->then(fn () => $one->lobbies->cache->set('1', $oneLobby))
                ->then(fn () => $two->lobbies->cache->set('1', $twoLobby))
                ->then(fn () => $mock->lobbies->addMember('1', '7'))
                ->then(fn () => $one->lobbies->leave($oneLobby))
                ->then(function () use ($mock, $one, $two, $botLobby, $twoLobby) {
                    $this->assertNotNull($botLobby->members->get('id', '7'));
                    $this->assertNull($twoLobby->members->get('id', '7'));

                    return $one->lobbies->cache->get('1')->then(function ($lobby) use ($mock, $two) {
                        $this->assertNull($lobby);

                        return $two->lobbies->cache->get('1')->then(function ($lobby) use ($mock) {
                            $this->assertSame('1', $lobby?->id);
                            $this->assertNull($lobby->members->get('id', '7'));

                            return $mock->lobbies->cache->get('1')->then(fn ($lobby) => $this->assertNotNull($lobby?->members->get('id', '7')));
                        });
                    });
                })
                ->then($resolve, $resolve);
        });
    }

    public function testRosterPromiseWaitsForCacheReadAndWrite()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn () => ['id' => '7']));
            $lobby = $this->lobby($mock);
            $read = new Deferred();
            $write = new Deferred();
            $cache = $this->getMockBuilder(CacheWrapper::class)->disableOriginalConstructor()->onlyMethods(['get', 'set'])->getMock();
            (new \ReflectionProperty($cache, 'discord'))->setValue($cache, $mock);
            $cache->expects($this->once())->method('get')->with('1')->willReturn($read->promise());
            $cache->expects($this->once())->method('set')->with('1', $lobby)->willReturnCallback(function () use ($lobby, $write) {
                $this->assertNotNull($lobby->members->get('id', '7'));

                return $write->promise();
            });
            (new \ReflectionProperty($mock->lobbies, 'cache'))->setValue($mock->lobbies, $cache);
            $settled = false;
            $promise = $mock->lobbies->addMember('1', '7')->then(function (Member $member) use (&$settled) {
                $settled = true;
                $this->assertSame('7', $member->id);
            });
            $this->assertFalse($settled);
            $this->assertNull($lobby->members->get('id', '7'));
            $read->resolve($lobby);
            $this->assertFalse($settled);
            $write->resolve(true);
            $this->assertTrue($settled);
            $promise->then($resolve, $resolve);
        });
    }

    private function lobby(Discord $discord): Lobby
    {
        return $discord->getFactory()->part(Lobby::class, ['id' => '1', 'application_id' => '2', 'members' => [
            ['id' => '5', 'metadata' => ['team' => 'red'], 'additional_name' => 'kept'],
            ['id' => '6'],
        ]], true);
    }
}

<?php

declare(strict_types=1);

use Discord\Discord;
use Discord\OAuth2\AccessToken;
use Discord\OAuth2\Session;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Member;
use React\Http\Message\Response;

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

    private function lobby(Discord $discord): Lobby
    {
        return $discord->getFactory()->part(Lobby::class, ['id' => '1', 'application_id' => '2', 'members' => [
            ['id' => '5', 'metadata' => ['team' => 'red'], 'additional_name' => 'kept'],
            ['id' => '6'],
        ]], true);
    }
}

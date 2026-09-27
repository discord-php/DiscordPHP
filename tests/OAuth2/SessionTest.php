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
use Discord\Parts\Channel\Invite;
use Discord\Parts\Guild\CommandPermissions;
use Discord\Parts\OAuth\Authorization;
use Discord\Parts\OAuth\UserInfo;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Message;
use Discord\Parts\Monetization\Entitlement;
use Discord\Parts\OAuth\Application;
use Discord\Parts\User\User;

final class SessionTest extends DiscordTestCase
{
    public function testTheUserIsFetchedWithTheirOwnToken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => ['id' => '5', 'username' => 'player', 'discriminator' => '0']);

            $session->getCurrentUser()
                ->then(function (User $user) use ($driver) {
                    $this->assertStringEndsWith('/users/@me', $driver->requests[0]['url']);
                    $this->assertSame('Bearer player-token', $driver->requests[0]['headers']['Authorization']);
                    $this->assertSame('player', $user->username);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testJoiningALobbySendsItsSecret()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => ['id' => '1', 'application_id' => '7', 'members' => [['id' => '5']]]);

            $session->lobbies->createOrJoin('match-42', ['member_metadata' => ['team' => 'red']])
                ->then(function (Lobby $lobby) use ($driver, $session) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/lobbies', $driver->requests[0]['url']);
                    $this->assertSame(['secret' => 'match-42', 'member_metadata' => ['team' => 'red']], $driver->requests[0]['content']);
                    $this->assertSame('Bearer player-token', $driver->requests[0]['headers']['Authorization']);
                    $this->assertSame('1', $lobby->id);

                    return $session->lobbies->cacheGet('1')->then(fn ($cached) => $this->assertSame('1', $cached?->id));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testLeavingALobbyIsAsThePlayer()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => null);

            $session->lobbies->leave('1')
                ->then(function () use ($driver) {
                    $this->assertSame('DELETE', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/lobbies/1/members/@me', $driver->requests[0]['url']);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAChannelIsLinkedAndUnlinked()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => ['id' => '1']);

            $session->lobbies->linkChannel('1', '99')
                ->then(fn () => $session->lobbies->unlinkChannel('1'))
                ->then(function () use ($driver) {
                    $this->assertStringEndsWith('/lobbies/1/channel-linking', $driver->requests[0]['url']);
                    $this->assertSame(['channel_id' => '99'], $driver->requests[0]['content']);
                    // Unlinking is the same route with no channel.
                    $this->assertSame('PATCH', $driver->requests[1]['method']);
                    $this->assertSame('{}', $driver->requests[1]['raw']);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testMessagesAreSentAndRead()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn (string $method) => $method === 'POST'
                ? ['id' => '9', 'content' => 'gg', 'lobby_id' => '1', 'channel_id' => '1']
                : [['id' => '9', 'content' => 'gg', 'lobby_id' => '1', 'channel_id' => '1']]);

            $session->lobbies->sendMessage('1', 'gg', ['metadata' => ['emote' => 'wave']])
                ->then(function (Message $message) use ($driver, $session) {
                    $this->assertSame(['content' => 'gg', 'metadata' => ['emote' => 'wave']], $driver->requests[0]['content']);
                    $this->assertSame('9', $message->id);

                    return $session->lobbies->getMessages('1', 20);
                })
                ->then(function ($messages) use ($driver) {
                    $this->assertStringEndsWith('/lobbies/1/messages?limit=20', $driver->requests[1]['url']);
                    $this->assertInstanceOf(Message::class, $messages->first());
                })
                ->then($resolve, $resolve);
        });
    }

    public function testThePlayerInvitesThemself()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => ['code' => 'abc123']);

            $session->lobbies->createInvite('1')
                ->then(function (Invite $invite) use ($driver) {
                    $this->assertStringEndsWith('/lobbies/1/members/@me/invites', $driver->requests[0]['url']);
                    $this->assertSame('abc123', $invite->code);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testEntitlementsDefaultToTheBotsApplication()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver, $mock] = $this->sessionWith(fn () => [['id' => '3', 'sku_id' => '4', 'application_id' => '7']]);
            $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

            $session->getEntitlements()
                ->then(function ($entitlements) use ($driver) {
                    $this->assertStringEndsWith('/users/@me/applications/7/entitlements', $driver->requests[0]['url']);
                    $this->assertInstanceOf(Entitlement::class, $entitlements->first());
                })
                ->then($resolve, $resolve);
        });
    }

    public function testWithoutAKnownApplicationTheCallIsRefusedNotSentBroken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => []);

            $session->getEntitlements()
                ->then(
                    fn () => $this->fail('should reject before the bot knows its application'),
                    fn (\Throwable $e) => $this->assertInstanceOf(\DomainException::class, $e)
                )
                ->then(fn () => $this->assertSame([], $driver->requests))
                ->then($resolve, $resolve);
        });
    }

    /**
     * A session for `player-token`, on a client whose requests are answered by `$respond`.
     *
     * @return array{0: Session, 1: object, 2: Discord}
     */
    public function testTheAuthorizationIsDescribed()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => [
                'application' => ['id' => '7', 'name' => 'Game'],
                'scopes' => ['identify', 'openid'],
                'expires' => '2026-10-03T00:00:00+00:00',
                'user' => ['id' => '5', 'username' => 'player', 'discriminator' => '0'],
            ]);

            $session->getAuthorization()
                ->then(function (Authorization $authorization) use ($driver) {
                    $this->assertStringEndsWith('/oauth2/@me', $driver->requests[0]['url']);
                    $this->assertSame('Bearer player-token', $driver->requests[0]['headers']['Authorization']);
                    $this->assertSame('Game', $authorization->application->name);
                    $this->assertSame(['identify', 'openid'], $authorization->scopes);
                    $this->assertSame('2026-10-03', $authorization->expires->format('Y-m-d'));
                    $this->assertSame('player', $authorization->user->username);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testOpenIdConnectDescribesTheUser()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver] = $this->sessionWith(fn () => ['sub' => '5', 'preferred_username' => 'player', 'email' => 'player@example.com', 'email_verified' => true]);

            $session->getUserInfo()
                ->then(function (UserInfo $info) use ($driver) {
                    $this->assertStringEndsWith('/oauth2/userinfo', $driver->requests[0]['url']);
                    $this->assertSame('Bearer player-token', $driver->requests[0]['headers']['Authorization']);
                    $this->assertSame('5', $info->sub);
                    $this->assertTrue($info->email_verified);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testCommandPermissionsAreSetWithTheUsersToken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$session, $driver, $mock] = $this->sessionWith(fn () => ['id' => '50', 'application_id' => '7', 'guild_id' => '10', 'permissions' => [['id' => '30', 'type' => 1, 'permission' => true]]]);
            $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

            $session->setCommandPermissions('10', '50', [['id' => '30', 'type' => 1, 'permission' => true]])
                ->then(function (CommandPermissions $permissions) use ($driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7/guilds/10/commands/50/permissions', $driver->requests[0]['url']);
                    $this->assertSame('Bearer player-token', $driver->requests[0]['headers']['Authorization'], 'Discord refuses a bot token here');
                    $this->assertSame(['permissions' => [['id' => '30', 'type' => 1, 'permission' => true]]], $driver->requests[0]['content']);
                    $this->assertSame('50', $permissions->id);
                })
                ->then($resolve, $resolve);
        });
    }

    private function sessionWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);

        return [new Session($mock, new AccessToken('player-token')), $driver, $mock];
    }
}

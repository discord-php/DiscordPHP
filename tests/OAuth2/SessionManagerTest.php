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
use Discord\OAuth2\SessionManager;
use Discord\OAuth2\TokenStore\ArrayTokenStore;
use Discord\Parts\OAuth\Application;

final class SessionManagerTest extends DiscordTestCase
{
    public function testAProvisionalAccountIsCreatedWithTheBotTokenAndStored()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver, $store] = $this->managerWith(fn () => ['access_token' => 'provisional', 'token_type' => 'Bearer', 'expires_in' => 604800, 'scope' => 'sdk.social_layer']);

            $manager->createProvisionalAccount('player-1', 'Lancelot', 'player-1')
                ->then(function (Session $session) use ($driver, $store) {
                    $this->assertStringEndsWith('/partner-sdk/token/bot', $driver->requests[0]['url']);
                    $this->assertSame('Bot ', substr($driver->requests[0]['headers']['Authorization'], 0, 4));
                    $this->assertSame(['external_user_id' => 'player-1', 'preferred_global_name' => 'Lancelot'], $driver->requests[0]['content']);
                    $this->assertSame('Bearer provisional', $session->getToken()->authorization());

                    return $store->get('player-1');
                })
                ->then(fn (?AccessToken $stored) => $this->assertSame('provisional', $stored?->access_token))
                ->then($resolve, $resolve);
        });
    }

    public function testAStoredSessionIsResumedAfterARestart()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, , $store] = $this->managerWith(fn () => []);

            $store->set('player-1', new AccessToken('stored', expires_at: time() + 3600))
                ->then(fn () => $manager->resume('player-1'))
                ->then(function (?Session $session) use ($manager) {
                    $this->assertSame('Bearer stored', $session?->getToken()->authorization());
                    $this->assertSame($session, $manager->get('player-1'), 'resumed sessions are kept open');

                    return $manager->resume('nobody');
                })
                ->then(fn ($missing) => $this->assertNull($missing))
                ->then($resolve, $resolve);
        });
    }

    public function testAnExpiredTokenIsRefreshedAsTheApplicationAndStored()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver, $store] = $this->managerWith(fn () => ['access_token' => 'fresh', 'token_type' => 'Bearer', 'expires_in' => 3600, 'refresh_token' => 'next', 'scope' => 'identify']);

            $store->set('player-1', new AccessToken('stale', 'Bearer', 'old-refresh', time() - 10))
                ->then(fn () => $manager->resume('player-1'))
                ->then(function (?Session $session) use ($driver, $store) {
                    $request = $driver->requests[0];
                    $this->assertStringEndsWith('/oauth2/token', $request['url']);
                    $this->assertSame('Basic '.base64_encode('7:secret'), $request['headers']['Authorization']);
                    $this->assertSame('application/x-www-form-urlencoded', $request['headers']['Content-Type']);
                    $this->assertSame('grant_type=refresh_token&refresh_token=old-refresh', $request['raw']);
                    $this->assertSame('Bearer fresh', $session?->getToken()->authorization());
                    $this->assertSame($driver, $session?->getHttpClient()->getDriver(), 'the refreshed client still shares the bot\'s driver');

                    return $store->get('player-1');
                })
                ->then(fn (?AccessToken $stored) => $this->assertSame('next', $stored?->refresh_token, 'the new refresh token replaces the old one'))
                ->then($resolve, $resolve);
        });
    }

    public function testAnExternalTokenIsExchangedWithTheApplicationsCredentialsAndNoAuthorization()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver] = $this->managerWith(fn () => ['access_token' => 'exchanged', 'token_type' => 'Bearer', 'expires_in' => 3600]);

            $manager->exchangeExternalToken('OIDC', 'provider-token')
                ->then(function (Session $session) use ($driver) {
                    $this->assertStringEndsWith('/partner-sdk/token', $driver->requests[0]['url']);
                    $this->assertArrayNotHasKey('Authorization', $driver->requests[0]['headers']);
                    $this->assertSame(['client_id' => '7', 'client_secret' => 'secret', 'external_auth_type' => 'OIDC', 'external_auth_token' => 'provider-token'], $driver->requests[0]['content']);
                    $this->assertSame('Bearer exchanged', $session->getToken()->authorization());
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAChildTokenIsExchangedForTheParents()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver] = $this->managerWith(fn () => ['access_token' => 'child', 'token_type' => 'Bearer', 'expires_in' => 3600]);

            $manager->exchangeChildToken(new AccessToken('parent'), '42')
                ->then(function (Session $session) use ($driver) {
                    $this->assertStringEndsWith('/partner-sdk/child-token', $driver->requests[0]['url']);
                    $this->assertSame(['parent_access_token' => 'parent', 'child_application_id' => '42', 'parent_client_secret' => 'secret'], $driver->requests[0]['content']);
                    $this->assertSame('Bearer child', $session->getToken()->authorization());
                })
                ->then($resolve, $resolve);
        });
    }

    public function testWithoutAClientSecretCredentialCallsAreRefusedNotSent()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver] = $this->managerWith(fn () => [], null);

            $manager->exchangeExternalToken('OIDC', 'provider-token')
                ->then(
                    fn () => $this->fail('should reject without a client secret'),
                    fn (\Throwable $e) => $this->assertStringContainsString('clientSecret', $e->getMessage())
                )
                ->then(fn () => $this->assertSame([], $driver->requests))
                ->then($resolve, $resolve);
        });
    }

    public function testOnlyTheMostRecentlyUsedSessionsStayOpen()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager] = $this->managerWith(fn () => [], 'secret', 2);

            $manager->open(new AccessToken('a'), 'a')
                ->then(fn () => $manager->open(new AccessToken('b'), 'b'))
                ->then(fn () => $manager->get('a'))
                ->then(fn () => $manager->open(new AccessToken('c'), 'c'))
                ->then(function () use ($manager) {
                    // `a` was used after `b`, so `b` is the one that goes.
                    $this->assertNotNull($manager->get('a'));
                    $this->assertNull($manager->get('b'));
                    $this->assertNotNull($manager->get('c'));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testForgettingASessionRemovesItsToken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, , $store] = $this->managerWith(fn () => []);

            $manager->open(new AccessToken('a'), 'a')
                ->then(fn () => $manager->forget('a'))
                ->then(fn () => $store->get('a'))
                ->then(function ($stored) use ($manager) {
                    $this->assertNull($stored);
                    $this->assertNull($manager->get('a'));
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * A manager for application 7, whose requests are answered by `$respond`.
     *
     * @return array{0: SessionManager, 1: object, 2: ArrayTokenStore}
     */
    public function testThePublicKeysComeAsAKeySet()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver] = $this->managerWith(fn () => ['keys' => [['kty' => 'RSA', 'kid' => 'k1', 'alg' => 'RS256', 'use' => 'sig', 'n' => 'abc', 'e' => 'AQAB']]]);

            $manager->getPublicKeys()
                ->then(function (array $keys) use ($driver) {
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/oauth2/keys', $driver->requests[0]['url']);
                    $this->assertSame([['kty' => 'RSA', 'kid' => 'k1', 'alg' => 'RS256', 'use' => 'sig', 'n' => 'abc', 'e' => 'AQAB']], $keys);
                })
                ->then($resolve, $resolve);
        });
    }

    private function managerWith(callable $respond, ?string $clientSecret = 'secret', int $limit = SessionManager::DEFAULT_LIMIT): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);
        $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

        $store = new ArrayTokenStore();

        return [new SessionManager($mock, $store, $clientSecret, $limit), $driver, $store];
    }
}

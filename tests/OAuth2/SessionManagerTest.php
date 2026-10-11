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
use Discord\OAuth2\TokenStore\TokenStoreInterface;
use Discord\Parts\Application\Application;
use React\Http\Message\Response;

use function React\Promise\reject;
use function React\Promise\resolve;

final class SessionManagerTest extends DiscordTestCase
{
    public function testAnAuthorizationCodeOpensAndStoresAPlayerSession()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver, $store] = $this->managerWith(fn () => [
                'access_token' => 'linked', 'refresh_token' => 'refresh', 'expires_in' => 3600,
                'token_type' => 'Bearer', 'scope' => 'identify application_identities.write',
            ]);

            $manager->exchangeAuthorizationCode('code +&=?', 'https://game.example/callback?flow=link&mode=game', 'player-1')
                ->then(function (Session $session) use ($manager, $driver, $store) {
                    $request = $driver->requests[0];
                    $this->assertSame('POST', $request['method']);
                    $this->assertStringEndsWith('/oauth2/token', $request['url']);
                    $this->assertSame('Basic '.base64_encode('7:secret'), $request['headers']['Authorization']);
                    $this->assertSame('application/x-www-form-urlencoded', $request['headers']['Content-Type']);
                    parse_str($request['raw'], $form);
                    $this->assertSame([
                        'grant_type' => 'authorization_code', 'code' => 'code +&=?',
                        'redirect_uri' => 'https://game.example/callback?flow=link&mode=game',
                    ], $form);
                    $this->assertSame('Bearer linked', $session->getToken()->authorization());
                    $this->assertSame('refresh', $session->getToken()->refresh_token);
                    $this->assertSame(['identify', 'application_identities.write'], $session->getToken()->scopes);
                    $this->assertSame($session, $manager->get('player-1'));

                    return $store->get('player-1')->then(fn ($token) => $this->assertSame($session->getToken(), $token));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnAuthorizationCodeCanOpenAnUnkeyedSession()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager] = $this->managerWith(fn () => ['access_token' => 'linked', 'expires_in' => 3600]);

            $manager->exchangeAuthorizationCode('code', 'https://game.example/callback')
                ->then(function (Session $session) {
                    $this->assertNull($session->getKey());
                    $this->assertSame('linked', $session->getToken()->access_token);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testRevocationUsesApplicationCredentialsAndForgetsTheStoredSession()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver, $store] = $this->managerWith(fn () => null);

            $manager->open(new AccessToken('access +&=?', refresh_token: 'refresh'), 'player-1')
                ->then(function (Session $session) use ($manager) {
                    return $manager->open(new AccessToken('other'), 'player-2')->then(fn () => $manager->revoke($session));
                })
                ->then(function (bool $deleted) use ($manager, $driver, $store) {
                    $this->assertTrue($deleted);
                    $request = $driver->requests[0];
                    $this->assertSame('POST', $request['method']);
                    $this->assertStringEndsWith('/oauth2/token/revoke', $request['url']);
                    $this->assertSame('Basic '.base64_encode('7:secret'), $request['headers']['Authorization']);
                    $this->assertSame('application/x-www-form-urlencoded', $request['headers']['Content-Type']);
                    parse_str($request['raw'], $form);
                    $this->assertSame(['token' => 'access +&=?', 'token_type_hint' => 'access_token'], $form);
                    $this->assertNull($manager->get('player-1'));
                    $this->assertNotNull($manager->get('player-2'));

                    return $store->get('player-1')->then(function ($stored) use ($manager) {
                        $this->assertNull($stored);

                        return $manager->resume('player-1')->then(fn ($session) => $this->assertNull($session));
                    });
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnUnkeyedSessionCanBeRevoked()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver] = $this->managerWith(fn () => null);

            $manager->open(new AccessToken('access'))
                ->then(fn (Session $session) => $manager->revoke($session))
                ->then(function (bool $deleted) use ($driver) {
                    $this->assertTrue($deleted);
                    $this->assertCount(1, $driver->requests);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAFailedExchangeKeepsTheExistingStoredSession()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, , $store] = $this->managerWith(fn () => new Response(400, ['Content-Type' => 'application/json'], '{"error":"invalid_grant"}'));

            $manager->open(new AccessToken('original'), 'player-1')
                ->then(fn () => $manager->exchangeAuthorizationCode('bad-code', 'https://game.example/callback', 'player-1'))
                ->then(fn () => $this->fail('Invalid grant must reject'), fn (\Throwable $error) => $this->assertNotNull($error))
                ->then(function () use ($manager, $store) {
                    $this->assertSame('original', $manager->get('player-1')->getToken()->access_token);

                    return $store->get('player-1')->then(fn ($token) => $this->assertSame('original', $token->access_token));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnInvalidTokenResponseDoesNotReplaceTheStoredSession()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, , $store] = $this->managerWith(fn () => ['scope' => 'identify']);

            $manager->open(new AccessToken('original'), 'player-1')
                ->then(fn () => $manager->exchangeAuthorizationCode('code', 'https://game.example/callback', 'player-1'))
                ->then(fn () => $this->fail('Missing access token must reject'), fn (\InvalidArgumentException $error) => $this->assertStringContainsString('access token', $error->getMessage()))
                ->then(function () use ($manager, $store) {
                    $this->assertSame('original', $manager->get('player-1')->getToken()->access_token);

                    return $store->get('player-1')->then(fn ($token) => $this->assertSame('original', $token->access_token));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAFailedRevocationKeepsTheSessionAndItsStoredToken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, , $store] = $this->managerWith(fn () => new Response(401, ['Content-Type' => 'application/json'], '{"message":"Unauthorized","code":0}'));

            $manager->open(new AccessToken('original'), 'player-1')
                ->then(fn (Session $session) => $manager->revoke($session))
                ->then(fn () => $this->fail('Revocation failure must reject'), fn (\Throwable $error) => $this->assertNotNull($error))
                ->then(function () use ($manager, $store) {
                    $this->assertSame('original', $manager->get('player-1')->getToken()->access_token);

                    return $store->get('player-1')->then(fn ($token) => $this->assertSame('original', $token->access_token));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testLinkingAndRevocationWithoutASecretRejectWithoutSendingRequests()
    {
        return wait(function (Discord $discord, $resolve) {
            [$manager, $driver, $store] = $this->managerWith(fn () => null, null);

            $manager->open(new AccessToken('original'), 'player-1')
                ->then(function (Session $session) use ($manager) {
                    return $manager->exchangeAuthorizationCode('code', 'https://game.example/callback')
                        ->then(fn () => $this->fail('Exchange needs a secret'), fn (\DomainException $error) => $this->assertStringContainsString('clientSecret', $error->getMessage()))
                        ->then(fn () => $manager->revoke($session))
                        ->then(fn () => $this->fail('Revocation needs a secret'), fn (\DomainException $error) => $this->assertStringContainsString('clientSecret', $error->getMessage()));
                })
                ->then(function () use ($manager, $driver, $store) {
                    $this->assertSame([], $driver->requests);
                    $this->assertNotNull($manager->get('player-1'));

                    return $store->get('player-1')->then(fn ($token) => $this->assertSame('original', $token->access_token));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testLinkingAndRevocationWaitForTheApplicationIdentity()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => null);
            $mock->getHttpClient()->setDriver($driver);
            $manager = new SessionManager($mock, new ArrayTokenStore(), 'secret');

            $manager->exchangeAuthorizationCode('code', 'https://game.example/callback')
                ->then(fn () => $this->fail('Exchange needs the application'), fn (\DomainException $error) => $this->assertStringContainsString('ready', $error->getMessage()))
                ->then(fn () => $manager->revoke(new Session($mock, new AccessToken('access'))))
                ->then(fn () => $this->fail('Revocation needs the application'), fn (\DomainException $error) => $this->assertStringContainsString('ready', $error->getMessage()))
                ->then(fn () => $this->assertSame([], $driver->requests))
                ->then($resolve, $resolve);
        });
    }

    public function testRevocationReportsLocalStoreCleanupFailure()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => null);
            $mock->getHttpClient()->setDriver($driver);
            $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);
            $store = $this->getMockBuilder(TokenStoreInterface::class)->getMock();
            $store->method('set')->willReturn(resolve(true));
            $store->expects($this->exactly(2))->method('delete')->willReturnOnConsecutiveCalls(resolve(false), reject(new \RuntimeException('Store unavailable')));
            $manager = new SessionManager($mock, $store, 'secret');

            $manager->open(new AccessToken('access'), 'player-1')
                ->then(fn (Session $session) => $manager->revoke($session))
                ->then(function (bool $deleted) use ($manager) {
                    $this->assertFalse($deleted);
                    $this->assertNull($manager->get('player-1'));

                    return $manager->open(new AccessToken('other'), 'player-2');
                })
                ->then(fn (Session $session) => $manager->revoke($session))
                ->then(fn () => $this->fail('Store rejection must propagate'), fn (\RuntimeException $error) => $this->assertSame('Store unavailable', $error->getMessage()))
                ->then(fn () => $this->assertCount(2, $driver->requests))
                ->then($resolve, $resolve);
        });
    }

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

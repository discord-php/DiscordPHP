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
use Discord\Parts\OAuth\Application;
use Discord\Parts\OAuth\ApplicationIdentity;
use Discord\Parts\OAuth\ApplicationIdentityProfile;

final class ApplicationIdentityTest extends DiscordTestCase
{
    public function testIdentitiesAreFoundByExternalId()
    {
        return wait(function (Discord $discord, $resolve) {
            [$application, $driver] = $this->applicationWith(fn () => ['identities' => [
                ['user_id' => '5', 'provider_type' => 'steam', 'provider_issued_user_id' => 'abc'],
            ]]);

            $application->identities->findByExternalId('steam', 'abc', 'eu')
                ->then(function ($identities) use ($driver) {
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7/application-identities/steam/abc?provider_id=eu', $driver->requests[0]['url']);
                    $this->assertCount(1, $identities);

                    $identity = $identities->first();
                    $this->assertInstanceOf(ApplicationIdentity::class, $identity);
                    $this->assertSame('5', $identity->user_id);
                    $this->assertSame('abc', $identity->provider_issued_user_id);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAUsersIdentitiesAreListedForThisApplication()
    {
        return wait(function (Discord $discord, $resolve) {
            [$application, $driver] = $this->applicationWith(fn () => ['identities' => []]);

            $application->identities->forUser('5')
                ->then(function ($identities) use ($driver) {
                    $this->assertStringEndsWith('/users/5/application-identities/7', $driver->requests[0]['url']);
                    $this->assertCount(0, $identities);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnIdentityIsDeletedAtItsOwnRoute()
    {
        return wait(function (Discord $discord, $resolve) {
            [$application, $driver] = $this->applicationWith(fn () => null);

            $application->identities->delete($application->identities->create(['user_id' => '5', 'provider_type' => 'steam', 'provider_id' => 'eu', 'provider_issued_user_id' => 'abc'], true))
                ->then(function () use ($driver) {
                    $this->assertSame('POST', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/users/5/application-identities/7/steam/abc/delete', $driver->requests[0]['url']);
                    $this->assertSame(['provider_id' => 'eu'], $driver->requests[0]['content']);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAProfileIsReadAndPublished()
    {
        return wait(function (Discord $discord, $resolve) {
            [$application, $driver] = $this->applicationWith(fn (string $method) => $method === 'GET'
                ? ['username' => 'lancelot', 'data' => ['primary' => ['rank_name' => 'Silver']]]
                : null);

            $application->identities->getProfile('5', 'abc')
                ->then(function (ApplicationIdentityProfile $profile) use ($driver, $application) {
                    $this->assertStringEndsWith('/applications/7/users/5/identities/abc/profile', $driver->requests[0]['url']);
                    $this->assertSame('lancelot', $profile->username);
                    $this->assertSame('Silver', $profile->data['primary']['rank_name']);

                    return $application->identities->updateProfile('5', 'abc', ['data' => ['primary' => ['rank_name' => 'Gold']]]);
                })
                ->then(function () use ($driver) {
                    $this->assertSame('PATCH', $driver->requests[1]['method']);
                    $this->assertStringEndsWith('/applications/7/users/5/identities/abc/profile', $driver->requests[1]['url']);
                    $this->assertSame(['data' => ['primary' => ['rank_name' => 'Gold']]], $driver->requests[1]['content']);
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * Application 7, on a client whose requests are answered by `$respond` rather than Discord.
     *
     * @return array{0: Application, 1: object}
     */
    private function applicationWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);

        return [$mock->getFactory()->part(Application::class, ['id' => '7'], true), $driver];
    }
}

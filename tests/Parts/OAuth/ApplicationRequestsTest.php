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
use Discord\Parts\User\Client;

/**
 * The application routes that Discord's OpenAPI description lists and DiscordPHP did not send before.
 */
final class ApplicationRequestsTest extends DiscordTestCase
{
    public function testTheApplicationIsFetchedByItsId()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => ['id' => '7', 'name' => 'Newsletter', 'description' => 'Daily news']);
            $application = $mock->getFactory()->part(Application::class, ['id' => '7']);

            $application->fetch()
                ->then(function ($fetched) use ($application, $driver) {
                    $this->assertSame($application, $fetched);
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7', $driver->requests[0]['url']);
                    $this->assertSame('Daily news', $application->description);
                    $this->assertTrue($application->created);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testTheApplicationIsEditedByItsId()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => ['id' => '7', 'name' => 'Newsletter', 'tags' => ['news']]);
            $application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

            $application->update(['tags' => ['news']])
                ->then(function ($updated) use ($application, $driver) {
                    $this->assertSame($application, $updated);
                    $this->assertSame('PATCH', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7', $driver->requests[0]['url']);
                    $this->assertSame(['tags' => ['news']], $driver->requests[0]['content']);
                    $this->assertSame(['news'], $application->tags);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testTheBotApplicationIsReadThroughTheOAuth2Route()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn (string $method, string $url) => str_ends_with($url, '/oauth2/applications/@me')
                ? ['id' => '7', 'name' => 'Newsletter', 'bot_public' => false]
                : ['id' => '7', 'name' => 'Newsletter']);
            /** @var Client $client */
            $client = $mock->getFactory()->part(Client::class, ['id' => '7', 'username' => 'Newsletter'], true);

            $client->getCurrentBotApplication()
                ->then(function ($application) use ($client, $driver) {
                    $this->assertSame($client->application, $application);
                    $this->assertSame('7', $application->id);
                    $this->assertFalse($application->bot_public);
                    $this->assertContains('GET', array_column(array_filter($driver->requests, fn ($request) => str_ends_with($request['url'], '/oauth2/applications/@me')), 'method'));
                })
                ->then($resolve, $resolve);
        });
    }

    private function clientWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);

        return [$mock, $driver];
    }
}

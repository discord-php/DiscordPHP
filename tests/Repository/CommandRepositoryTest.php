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

use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\OAuth\Application;
use Discord\Repository\Guild\GuildCommandRepository;
use Discord\Repository\Interaction\GlobalCommandRepository;

final class CommandRepositoryTest extends DiscordTestCase
{
    public function testGlobalCommandsAreOverwrittenInOneRequest()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => [
                ['id' => '1', 'application_id' => '7', 'name' => 'ping', 'description' => 'Pong', 'type' => 1],
                ['id' => '2', 'application_id' => '7', 'name' => 'info', 'description' => 'About', 'type' => 1],
            ]);
            $commands = $mock->getFactory()->repository(GlobalCommandRepository::class, ['application_id' => '7']);
            $commands->pushItem($commands->create(['id' => 'old', 'application_id' => '7', 'name' => 'old', 'description' => 'Gone'], true));

            $commands->bulkOverwrite([
                CommandBuilder::new()->setName('ping')->setDescription('Pong'),
                ['name' => 'info', 'description' => 'About', 'type' => 1],
            ])
                ->then(function ($repository) use ($commands, $driver) {
                    $this->assertSame($commands, $repository);
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7/commands', $driver->requests[0]['url']);
                    $this->assertSame(['ping', 'info'], array_column($driver->requests[0]['content'], 'name'));
                    $this->assertSame('ping', $commands->get('id', '1')?->name);
                    $this->assertSame('info', $commands->get('id', '2')?->name);
                    $this->assertNull($commands->get('id', 'old'), 'a command missing from the list is gone');
                })
                ->then($resolve, $resolve);
        });
    }

    public function testGuildCommandsAreOverwrittenForTheirGuild()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => []);
            $commands = $mock->getFactory()->repository(GuildCommandRepository::class, ['guild_id' => '10']);

            $commands->bulkOverwrite([])
                ->then(function () use ($driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7/guilds/10/commands', $driver->requests[0]['url']);
                    $this->assertSame([], $driver->requests[0]['content'], 'an empty list deletes every command');
                })
                ->then($resolve, $resolve);
        });
    }

    public function testSomethingThatIsNotACommandIsRefused()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => []);
            $commands = $mock->getFactory()->repository(GlobalCommandRepository::class, ['application_id' => '7']);

            $commands->bulkOverwrite(['ping'])
                ->then(
                    fn () => $this->fail('A string is not a command.'),
                    function (\Throwable $e) use ($driver) {
                        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
                        $this->assertSame([], $driver->requests);
                    },
                )
                ->then($resolve, $resolve);
        });
    }

    private function clientWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);
        $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

        return [$mock, $driver];
    }
}

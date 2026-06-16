<?php

declare(strict_types=1);

use Discord\Discord;
use Discord\MessageCommandClient as MessageCommandClientClass;
use Discord\MessageCommandClient\Command;

final class MessageCommandClientTest extends \DiscordTestCase
{
    public function testCanRegisterAndRetrieveCommand()
    {
        return wait(function (Discord $discord, $resolve) {
            $client = new MessageCommandClientClass([
                'token' => getenv('DISCORD_TOKEN'),
            ]);

            $client->registerCommand('hello', fn () => 'world', ['description' => 'desc']);

            $command = $client->getCommand('hello');

            $this->assertInstanceOf(Command::class, $command);
            $this->assertSame('hello', $command->command);

            $resolve(null);
        });
    }

    public function testBuildCommandCreatesCommandInstance()
    {
        return wait(function (Discord $discord, $resolve) {
            $client = new MessageCommandClientClass([
                'token' => getenv('DISCORD_TOKEN'),
            ]);

            $result = $client->buildCommand('foo', fn () => 'bar', []);

            $this->assertArrayHasKey('command', $result);
            $this->assertInstanceOf(Command::class, $result['command']);
            $this->assertSame('foo', $result['command']->command);

            $resolve(null);
        });
    }
}

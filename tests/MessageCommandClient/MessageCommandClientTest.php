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

            $builtCommand = $client->buildCommand('foo', fn () => 'bar', []);

            $this->assertInstanceOf(Command::class, $builtCommand->command);
            $this->assertSame('foo', $builtCommand->command->command);

            $resolve(null);
        });
    }
}

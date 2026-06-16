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

use Discord\MessageCommandClient as MessageCommandClientClass;
use Discord\MessageCommandClient\Command;
use PHPUnit\Framework\TestCase;

use Discord\MessageCommandClient\BuiltCommand;

final class CommandRegistrationTest extends TestCase
{
    public function testCaseInsensitiveSubcommandDuplicateThrows()
    {
        $this->expectException(\RuntimeException::class);

        $client = $this->getMockBuilder(MessageCommandClientClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('getCommandClientOptions')->willReturn(['caseInsensitiveCommands' => true]);
        $client->method('normalizeCommandName')->willReturnCallback(function ($name) use ($client) {
            $ci = $client->getCommandClientOptions()['caseInsensitiveCommands'];

            return $ci ? (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name)) : $name;
        });
        $client->method('buildCommand')->willReturnCallback(function ($name, $callable, $options) use ($client) {
            $cmd = new Command($client, $name, $callable, [
                'description' => '',
                'longDescription' => '',
                'usage' => '',
                'cooldown' => 0,
                'cooldownMessage' => '',
                'showHelp' => true,
            ]);

            return new BuiltCommand($cmd, is_array($options) ? array_merge(['aliases' => []], $options) : ['aliases' => []]);
        });

        $parent = new Command($client, 'parent', fn () => null, [
            'description' => '',
            'longDescription' => '',
            'usage' => '',
            'cooldown' => 0,
            'cooldownMessage' => '',
            'showHelp' => true,
        ]);

        $parent->registerSubCommand('foo', fn () => null, []);

        // Should throw because 'FOO' normalizes to 'foo'
        $parent->registerSubCommand('FOO', fn () => null, []);
    }

    public function testSubcommandNameCollidesWithAliasThrows()
    {
        $this->expectException(\RuntimeException::class);

        $client = $this->getMockBuilder(MessageCommandClientClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('getCommandClientOptions')->willReturn(['caseInsensitiveCommands' => true]);
        $client->method('normalizeCommandName')->willReturnCallback(function ($name) use ($client) {
            $ci = $client->getCommandClientOptions()['caseInsensitiveCommands'];

            return $ci ? (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name)) : $name;
        });
        $client->method('buildCommand')->willReturnCallback(function ($name, $callable, $options) use ($client) {
            $cmd = new Command($client, $name, $callable, [
                'description' => '',
                'longDescription' => '',
                'usage' => '',
                'cooldown' => 0,
                'cooldownMessage' => '',
                'showHelp' => true,
            ]);

            return new BuiltCommand($cmd, is_array($options) ? array_merge(['aliases' => []], $options) : ['aliases' => []]);
        });

        $parent = new Command($client, 'parent', fn () => null, [
            'description' => '',
            'longDescription' => '',
            'usage' => '',
            'cooldown' => 0,
            'cooldownMessage' => '',
            'showHelp' => true,
        ]);

        // Register a subcommand with an alias 'bar'
        $parent->registerSubCommand('foo', fn () => null, ['aliases' => ['bar']]);

        // Registering a subcommand named 'BAR' should collide with alias 'bar' after normalization
        $parent->registerSubCommand('BAR', fn () => null, []);
    }

    public function testCaseSensitiveAllowsCaseVariants()
    {
        $client = $this->getMockBuilder(MessageCommandClientClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('getCommandClientOptions')->willReturn(['caseInsensitiveCommands' => false]);
        $client->method('normalizeCommandName')->willReturnCallback(function ($name) use ($client) {
            $ci = $client->getCommandClientOptions()['caseInsensitiveCommands'];

            return $ci ? (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name)) : $name;
        });
        $client->method('buildCommand')->willReturnCallback(function ($name, $callable, $options) use ($client) {
            $cmd = new Command($client, $name, $callable, [
                'description' => '',
                'longDescription' => '',
                'usage' => '',
                'cooldown' => 0,
                'cooldownMessage' => '',
                'showHelp' => true,
            ]);

            return new BuiltCommand($cmd, is_array($options) ? array_merge(['aliases' => []], $options) : ['aliases' => []]);
        });

        $parent = new Command($client, 'parent', fn () => null, [
            'description' => '',
            'longDescription' => '',
            'usage' => '',
            'cooldown' => 0,
            'cooldownMessage' => '',
            'showHelp' => true,
        ]);

        $parent->registerSubCommand('foo', fn () => null, []);
        $parent->registerSubCommand('FOO', fn () => null, []);

        $this->assertInstanceOf(Command::class, $parent->getCommand('foo'));
        $this->assertInstanceOf(Command::class, $parent->getCommand('FOO'));
    }
}

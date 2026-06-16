<?php

declare(strict_types=1);

use Discord\MessageCommandClient as MessageCommandClientClass;
use Discord\MessageCommandClient\Command;
use PHPUnit\Framework\TestCase;

final class CommandRegistrationTest extends TestCase
{
    public function testCaseInsensitiveSubcommandDuplicateThrows()
    {
        $this->expectException(\RuntimeException::class);

        $client = $this->getMockBuilder(MessageCommandClientClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client->method('getCommandClientOptions')->willReturn(['caseInsensitiveCommands' => true]);
        $client->method('buildCommand')->willReturnCallback(function ($name, $callable, $options) use ($client) {
            $cmd = new Command($client, $name, $callable, '', '', '', 0, '', true);
            $resolved = array_merge(['aliases' => []], is_array($options) ? $options : []);
            return ['command' => $cmd, 'options' => $resolved];
        });

        $parent = new Command($client, 'parent', fn () => null, '', '', '', 0, '', true);

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
        $client->method('buildCommand')->willReturnCallback(function ($name, $callable, $options) use ($client) {
            $cmd = new Command($client, $name, $callable, '', '', '', 0, '', true);
            $resolved = array_merge(['aliases' => []], is_array($options) ? $options : []);
            return ['command' => $cmd, 'options' => $resolved];
        });

        $parent = new Command($client, 'parent', fn () => null, '', '', '', 0, '', true);

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
        $client->method('buildCommand')->willReturnCallback(function ($name, $callable, $options) use ($client) {
            $cmd = new Command($client, $name, $callable, '', '', '', 0, '', true);
            $resolved = array_merge(['aliases' => []], is_array($options) ? $options : []);
            return ['command' => $cmd, 'options' => $resolved];
        });

        $parent = new Command($client, 'parent', fn () => null, '', '', '', 0, '', true);

        $parent->registerSubCommand('foo', fn () => null, []);
        $parent->registerSubCommand('FOO', fn () => null, []);

        $this->assertInstanceOf(Command::class, $parent->getCommand('foo'));
        $this->assertInstanceOf(Command::class, $parent->getCommand('FOO'));
    }
}




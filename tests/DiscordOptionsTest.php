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
use Discord\Exceptions\IntentException;
use Discord\Helpers\Collection;
use Discord\WebSockets\Intents;
use Monolog\Logger as Monolog;
use Psr\Log\NullLogger;
use React\Dns\Config\Config as DnsConfig;
use React\EventLoop\Loop;
use React\EventLoop\StreamSelectLoop;

/**
 * The client options as `resolveOptions()` hands them to the constructor.
 */
final class DiscordOptionsTest extends DiscordTestCase
{
    /** Resolves `$options` without constructing (and connecting) a client. */
    private function resolve(array $options): array
    {
        $client = (new ReflectionClass(Discord::class))->newInstanceWithoutConstructor();

        return (new ReflectionMethod(Discord::class, 'resolveOptions'))->invoke($client, $options + ['token' => 'token']);
    }

    public function testDefaultsFillWhatWasNotPassed(): void
    {
        $options = $this->resolve([]);

        $this->assertInstanceOf(Monolog::class, $options['logger']);
        $this->assertSame(Loop::get(), $options['loop']);
        $this->assertInstanceOf(DnsConfig::class, $options['dnsConfig']);
        $this->assertNotEmpty($options['dnsConfig']->nameservers);
        $this->assertSame(Intents::getDefaultIntents(), $options['intents']);
        $this->assertFalse($options['socket_options']['happy_eyeballs']);
        $this->assertSame(Collection::class, $options['collection']);
    }

    public function testWhatWasPassedIsKept(): void
    {
        $logger = new NullLogger();
        $loop = new StreamSelectLoop();

        $options = $this->resolve([
            'logger' => $logger,
            'loop' => $loop,
            'dnsConfig' => '1.1.1.1',
            'socket_options' => ['timeout' => 5],
        ]);

        $this->assertSame($logger, $options['logger']);
        $this->assertSame($loop, $options['loop']);
        $this->assertSame('1.1.1.1', $options['dnsConfig']);
        $this->assertSame(['timeout' => 5, 'happy_eyeballs' => false], $options['socket_options']);
    }

    public function testAnExplicitNullLoggerGetsTheDefaultOne(): void
    {
        $this->assertInstanceOf(Monolog::class, $this->resolve(['logger' => null])['logger']);
    }

    public function testIntentsAndCapabilitiesGivenAsListsBecomeBitmasks(): void
    {
        $options = $this->resolve([
            'intents' => [Intents::GUILDS, Intents::GUILD_MESSAGES],
            'capabilities' => [1, 4],
        ]);

        $this->assertSame(Intents::GUILDS | Intents::GUILD_MESSAGES, $options['intents']);
        $this->assertSame(5, $options['capabilities']);
        $this->assertNull($this->resolve(['capabilities' => []])['capabilities']);
    }

    public function testAnUnknownIntentIsRefused(): void
    {
        $this->expectException(IntentException::class);

        $this->resolve(['intents' => [Intents::GUILDS, 1 << 40]]);
    }

    public function testLoadingAllMembersNeedsTheGuildMembersIntent(): void
    {
        $this->expectException(IntentException::class);

        $this->resolve(['loadAllMembers' => true, 'intents' => [Intents::GUILDS]]);
    }

    public function testAnUnusableCollectionClassFallsBackToTheDefault(): void
    {
        $this->assertSame(Collection::class, $this->resolve(['collection' => stdClass::class])['collection']);
    }
}

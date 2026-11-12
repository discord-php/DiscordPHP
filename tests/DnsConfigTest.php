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
use PHPUnit\Framework\TestCase;
use React\Dns\Config\Config;

/**
 * System DNS lookup must not depend on `wmic`, which recent Windows no longer ships.
 */
final class DnsConfigTest extends TestCase
{
    private static function parse(string $output): array
    {
        $method = new ReflectionMethod(Discord::class, 'parseNameservers');

        return $method->invoke(null, $output);
    }

    public function testParsesPowerShellOutputIpv4First(): void
    {
        $output = "fe80::1%12\r\n192.168.1.1\r\n\r\n8.8.8.8\r\n192.168.1.1\r\nfec0:0:0:ffff::1\r\n2001:4860:4860::8888\r\n";

        $this->assertSame(['192.168.1.1', '8.8.8.8', 'fe80::1', '2001:4860:4860::8888'], self::parse($output));
    }

    public function testIgnoresErrorText(): void
    {
        $this->assertSame([], self::parse("'wmic' is not recognized as an internal or external command,\noperable program or batch file.\n"));
    }

    public function testLoadSystemDnsConfigReturnsConfig(): void
    {
        $method = new ReflectionMethod(Discord::class, 'loadSystemDnsConfig');

        $this->assertInstanceOf(Config::class, $method->invoke(null));
    }

    public function testResolvedNameserverIsSharedWithSocketConnectors(): void
    {
        $config = new Config();
        $config->nameservers = ['1.1.1.1'];

        $method = new ReflectionMethod(Discord::class, 'resolveOptions');
        $discord = (new ReflectionClass(Discord::class))->newInstanceWithoutConstructor();
        $options = $method->invoke($discord, [
            'token' => 'dns-test-token',
            'dnsConfig' => $config,
            'logger' => new Psr\Log\NullLogger(),
        ]);

        $this->assertSame('1.1.1.1', $options['socket_options']['dns']);
    }
}

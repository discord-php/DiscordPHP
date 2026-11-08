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
use Discord\DiscordCommandClient;
use Discord\MessageCommandClient;
use Discord\WebSockets\Op;
use Discord\WebSockets\Payload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The bot token must never show up in stack traces or debug output.
 */
final class TokenRedactionTest extends TestCase
{
    private const TOKEN = 'redaction-test-token-value';

    public static function sensitiveParameters(): array
    {
        return [
            'Discord::__construct' => [Discord::class, '__construct', 'options'],
            'Discord::resolveOptions' => [Discord::class, 'resolveOptions', 'options'],
            'Discord::resume' => [Discord::class, 'resume', 'token'],
            'MessageCommandClient::__construct' => [MessageCommandClient::class, '__construct', 'options'],
            'MessageCommandClient::resolveOptions' => [MessageCommandClient::class, 'resolveOptions', 'options'],
            'DiscordCommandClient::__construct' => [DiscordCommandClient::class, '__construct', 'options'],
            'DiscordCommandClient::resolveCommandClientOptions' => [DiscordCommandClient::class, 'resolveCommandClientOptions', 'options'],
            'Payload::__construct' => [Payload::class, '__construct', 'd'],
            'Payload::new' => [Payload::class, 'new', 'd'],
            'Discord::resolveWithoutSecrets' => [Discord::class, 'resolveWithoutSecrets', 'options'],
        ];
    }

    #[DataProvider('sensitiveParameters')]
    public function testTokenBearingParametersAreSensitive(string $class, string $method, string $parameter)
    {
        foreach ((new ReflectionMethod($class, $method))->getParameters() as $param) {
            if ($param->getName() === $parameter) {
                $this->assertNotEmpty($param->getAttributes(SensitiveParameter::class), "$class::$method(\$$parameter)");

                return;
            }
        }

        $this->fail("$class::$method has no \$$parameter parameter");
    }

    public function testAConstructorErrorDoesNotPutTheTokenInTheTrace()
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');

        try {
            new Discord(['token' => self::TOKEN, 'logger' => new NullLogger(), 'intents' => 'not-an-intent']);
            $this->fail('Expected the options to be rejected.');
        } catch (Throwable $e) {
            $this->assertStringNotContainsString(self::TOKEN, $e->getTraceAsString());
            $this->assertStringNotContainsString(self::TOKEN, print_r($e->getTrace(), true));
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    public function testACommandClientErrorDoesNotPutTheTokenInTheTrace()
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');

        try {
            new DiscordCommandClient(['token' => self::TOKEN, 'internalRejectedPromiseHandler' => 'not-callable-at-all']);
            $this->fail('Expected the options to be rejected.');
        } catch (Throwable $e) {
            $this->assertStringNotContainsString(self::TOKEN, print_r($e->getTrace(), true));
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    public function testAMissingTokenIsStillRejected()
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\MissingOptionsException::class);

        new Discord(['logger' => new NullLogger()]);
    }

    public function testAGivenNameserverSkipsTheSystemDnsLookup()
    {
        $discord = (new ReflectionClass(Discord::class))->newInstanceWithoutConstructor();
        $options = (new ReflectionMethod(Discord::class, 'resolveOptions'))->invoke($discord, [
            'token' => self::TOKEN,
            'logger' => new NullLogger(),
            'socket_options' => ['dns' => '1.1.1.1'],
        ]);

        $this->assertSame(['1.1.1.1'], $options['dnsConfig']->nameservers);
    }

    public function testIdentifyAndResumeStillSendTheToken()
    {
        $client = new class(['token' => self::TOKEN, 'logger' => new NullLogger()]) extends OfflineDiscordClient {
            public array $sent = [];

            public function send(object|array $data, bool $force = false): void
            {
                $this->sent[] = $data;
            }
        };

        $client->identify();
        $client->resume(self::TOKEN, 'session', 5);

        [$identify, $resume] = $client->sent;
        $this->assertSame(Op::OP_IDENTIFY, $identify->op);
        $this->assertSame(self::TOKEN, $identify->d['token']);
        $this->assertArrayHasKey('properties', $identify->d);
        $this->assertSame(Op::OP_RESUME, $resume->op);
        $this->assertSame(['token' => self::TOKEN, 'session_id' => 'session', 'seq' => 5], $resume->d);
        $this->assertStringNotContainsString(self::TOKEN, print_r($identify, true));
    }
}

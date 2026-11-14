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

require __DIR__.'/../../vendor/autoload.php';

use Discord\Http\DriverInterface;
use Discord\Http\Endpoint;
use Discord\Http\Request;
use Discord\MessageCommandClient;
use Psr\Log\NullLogger;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

// The real transport classes are never loaded in this process. Both guards return
// unresolved promises, so even the deliberately unsafe control cannot connect.
final class GuardHttpDriver implements DriverInterface
{
    public static int $constructed = 0;
    public static int $calls = 0;
    public static array $urls = [];

    public function __construct(...$args)
    {
        ++self::$constructed;
    }

    public function runRequest($request): PromiseInterface
    {
        ++self::$calls;
        self::$urls[] = $request->getUrl();

        return (new Deferred())->promise();
    }
}

final class GuardWebSocketConnector
{
    public static int $calls = 0;

    public function __construct(...$args)
    {
    }

    public function __invoke(...$args): PromiseInterface
    {
        ++self::$calls;

        return (new Deferred())->promise();
    }
}

class_alias(GuardHttpDriver::class, 'Discord\\Http\\Drivers\\React');
class_alias(GuardWebSocketConnector::class, 'Ratchet\\Client\\Connector');
require __DIR__.'/../functions.php';

// The unchanged constructor is a positive control for the guard. It must reach
// the substituted HTTP driver, which leaves the request pending without I/O.
new MessageCommandClient(['token' => '', 'logger' => new NullLogger(), 'dnsConfig' => new \React\Dns\Config\Config(), 'socket_options' => ['dns' => false]]);
Loop::futureTick(static fn () => Loop::stop());
Loop::run();
if (! in_array('https://discord.com/api/v10/applications/@me', GuardHttpDriver::$urls, true)
    || ! in_array('https://discord.com/api/v10/gateway/bot', GuardHttpDriver::$urls, true)
    || GuardWebSocketConnector::$calls !== 0) {
    throw new RuntimeException('HTTP transport guard did not detect the unsafe control.');
}
$controlCalls = GuardHttpDriver::$calls;

// The WebSocket guard must also detect an attempted invocation independently.
(new GuardWebSocketConnector())('wss://synthetic.invalid');
if (GuardWebSocketConnector::$calls !== 1) {
    throw new RuntimeException('WebSocket transport guard did not detect its control.');
}
GuardWebSocketConnector::$calls = 0;

$clients = [
    getMockDiscord(),
    getMockMessageCommandClient(),
    getOfflineDiscord(),
    new class(['token' => '', 'logger' => new NullLogger()]) extends OfflineMessageCommandClient {
        public function send(object|array $data, bool $force = false): void
        {
        }
    },
    new class(['token' => '', 'logger' => new NullLogger()]) extends OfflineDiscordClient {
        public function connectWs(): void
        {
        }
    },
];
Loop::futureTick(static fn () => Loop::stop());
Loop::run();
if (GuardHttpDriver::$calls !== $controlCalls || GuardWebSocketConnector::$calls !== 0) {
    throw new RuntimeException(sprintf('Offline startup invoked a real transport entry point (HTTP %d to %d; WebSocket %d; URLs %s).', $controlCalls, GuardHttpDriver::$calls, GuardWebSocketConnector::$calls, implode(', ', GuardHttpDriver::$urls)));
}
if (GuardHttpDriver::$constructed !== 6) {
    throw new RuntimeException('Unexpected HTTP driver construction count.');
}

foreach ($clients as $client) {
    $driver = $client->getHttpClient()->getDriver();
    if (! $driver instanceof DriverInterface || $driver instanceof GuardHttpDriver) {
        throw new RuntimeException('Offline startup did not install the fake HTTP driver.');
    }

    // Unexpected requests fail closed; the callback cannot access the network.
    try {
        $driver->runRequest(new Request(new Deferred(), 'get', Endpoint::bind(Endpoint::GATEWAY), ''));
        throw new RuntimeException('Offline default driver accepted an unscripted request.');
    } catch (LogicException $expected) {
        if (! str_starts_with($expected->getMessage(), 'Unexpected offline HTTP request:')) {
            throw $expected;
        }
    }
}

echo "Transport guard detected application and gateway HTTP controls; offline startup made zero HTTP/WebSocket transport calls.\n";

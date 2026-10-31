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
use Discord\Http\DriverInterface;
use Discord\Http\Http;
use Discord\Http\Request;
use Discord\MessageCommandClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use React\Http\Message\Response;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

const TIMEOUT = 10;

trait OfflineClientStartup
{
    private bool $offlineDriverInstalled = false;

    protected function resolveOptions(array $options = []): array
    {
        $options += ['dnsConfig' => new \React\Dns\Config\Config()];
        $options['socket_options'] = ['dns' => false] + ($options['socket_options'] ?? []);

        return parent::resolveOptions($options);
    }

    public function getHttpClient(): Http
    {
        $http = parent::getHttpClient();
        if (! $this->offlineDriverInstalled) {
            $http->setDriver(getMockHttpDriver(static function (string $method, string $url) {
                throw new LogicException("Unexpected offline HTTP request: $method $url");
            }, ['/gateway/bot', '/applications/@me']));
            $this->offlineDriverInstalled = true;
        }

        return $http;
    }

    public function connectWs(): void
    {
        $this->getHttpClient();
    }
}

class OfflineDiscordClient extends Discord
{
    use OfflineClientStartup;
}

class OfflineMessageCommandClient extends MessageCommandClient
{
    use OfflineClientStartup;
}

function wait(callable $callback, float $timeout = TIMEOUT, ?callable $timeoutFn = null)
{
    $discord = DiscordSingleton::get();

    $result = null;
    $finally = null;
    $timedOut = false;

    $discord->getLoop()->futureTick(function () use ($callback, $discord, &$result, &$finally) {
        $resolve = function ($x = null) use ($discord, &$result) {
            $result = $x;
            $discord->getLoop()->stop();
        };

        try {
            $finally = $callback($discord, $resolve);
        } catch (\Throwable $e) {
            $resolve($e);
        }
    });

    $timeout = $discord->getLoop()->addTimer($timeout, function () use ($discord, &$timedOut) {
        $timedOut = true;
        $discord->getLoop()->stop();
    });

    $discord->getLoop()->run();
    $discord->getLoop()->cancelTimer($timeout);

    if ($result instanceof Exception) {
        throw $result;
    }

    if (is_callable($finally)) {
        $finally();
    }

    if ($timedOut) {
        if ($timeoutFn !== null) {
            $timeoutFn();
        } else {
            throw new \Exception('Timed out');
        }
    }

    return $result;
}

function getMockDiscord(array $options = []): Discord
{
    return getMockMessageCommandClient($options);
}

function getMockMessageCommandClient(array $options = []): MessageCommandClient
{
    return new OfflineMessageCommandClient($options + ['token' => '', 'logger' => new NullLogger()]);
}

function getOfflineDiscord(array $options = []): Discord
{
    return new OfflineDiscordClient($options + ['token' => '', 'logger' => new NullLogger()]);
}

/**
 * An HTTP driver that answers from a script instead of the network, and records what it was asked.
 *
 * `$respond` receives the method and URL and returns the decoded response body; `null` answers 204 No Content,
 * and a response is sent as it is.
 */
function getMockHttpDriver(callable $respond, array $pendingPaths = ['/gateway/bot']): DriverInterface
{
    return new class($respond, $pendingPaths) implements DriverInterface {
        /** @var array<int, array{method: string, url: string, content: mixed, raw: string, headers: array<string, string>}> */
        public array $requests = [];

        public function __construct(private $respond, private array $pendingPaths)
        {
        }

        public function runRequest(Request $request): PromiseInterface
        {
            foreach ($this->pendingPaths as $path) {
                if (str_ends_with($request->getUrl(), $path)) {
                    return (new Deferred())->promise();
                }
            }

            $method = strtoupper($request->getMethod());

            $this->requests[] = [
                'method' => $method,
                'url' => $request->getUrl(),
                'content' => json_decode($request->getContent() ?: 'null', true),
                'raw' => $request->getContent(),
                'headers' => $request->getHeaders(),
            ];

            $body = ($this->respond)($method, $request->getUrl());

            if ($body instanceof ResponseInterface) {
                return resolve($body);
            }

            return resolve($body === null
                ? new Response(204)
                : new Response(200, ['Content-Type' => 'application/json'], json_encode($body)));
        }
    };
}

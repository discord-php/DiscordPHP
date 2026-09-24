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
use Discord\Http\Request;
use Discord\MessageCommandClient;
use Psr\Log\NullLogger;
use React\Http\Message\Response;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

const TIMEOUT = 10;

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

function getMockDiscord(): Discord
{
    return new MessageCommandClient(['token' => '', 'logger' => new NullLogger()]);
}

function getMockMessageCommandClient(): MessageCommandClient
{
    return new MessageCommandClient(['token' => '', 'logger' => new NullLogger()]);
}

/**
 * An HTTP driver that answers from a script instead of the network, and records what it was asked.
 *
 * `$respond` receives the method and URL and returns the decoded response body; `null` answers 204 No Content.
 */
function getMockHttpDriver(callable $respond): DriverInterface
{
    return new class ($respond) implements DriverInterface {
        /** @var array<int, array{method: string, url: string, content: mixed}> */
        public array $requests = [];

        public function __construct(private $respond)
        {
        }

        public function runRequest(Request $request): PromiseInterface
        {
            // The client asks for the gateway as soon as it is built. Leave that
            // unanswered, so it never connects, and out of what tests inspect.
            if (preg_match('#/gateway(/bot)?$#', $request->getUrl()) === 1) {
                return (new Deferred())->promise();
            }

            $method = strtoupper($request->getMethod());

            $this->requests[] = [
                'method' => $method,
                'url' => $request->getUrl(),
                'content' => json_decode($request->getContent() ?: 'null', true),
            ];

            $body = ($this->respond)($method, $request->getUrl());

            return resolve($body === null
                ? new Response(204)
                : new Response(200, ['Content-Type' => 'application/json'], json_encode($body)));
        }
    };
}

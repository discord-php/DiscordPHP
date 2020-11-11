<?php declare(strict_types=1);

use Discord\Discord;
use Discord\Helpers\Deferred;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;

function timeout(string $name, int $timeout, LoopInterface $loop): ExtendedPromiseInterface
{
    $deferred = new Deferred();

    $GLOBALS['timeouts'][$name] = $loop->addTimer($timeout, function () use ($deferred) {
        $deferred->resolve();
    });

    return $deferred->promise();
}

function cancelTimeout(string $name, LoopInterface $loop)
{
    $timeouts = $GLOBALS['timeouts'] ?? [];

    if (isset($timeouts[$name])) {
        $loop->cancelTimer($timeouts[$name]);
    }
}

function wait(Discord $discord, callable $callback)
{
    $discord->getLoop()->futureTick(function () use ($callback, $discord) {
        $resolve = function ($x = null) use ($discord) {
            $GLOBALS['next'] = $x;
            $discord->getLoop()->stop();
        };

        $callback($discord, $GLOBALS['next'] ?? null, $resolve);
    });

    $discord->getLoop()->run();
    return $discord;
}

<?php declare(strict_types=1);

use Discord\Discord;
use Discord\Helpers\Deferred;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;

const TIMEOUT = 5;

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

function wait(callable $callback, float $timeout = TIMEOUT)
{
    $discord = DiscordSingleton::get();

    $result = null;
    $discord->getLoop()->futureTick(function () use ($callback, $discord, &$result) {
        $resolve = function ($x = null) use ($discord, &$result) {
            $result = $x;
            $discord->getLoop()->stop();
        };

        $callback($discord, $resolve);
    });

    $timeout = $discord->getLoop()->addTimer($timeout, function () use ($discord, &$timedOut) {
        throw new \Exception('Timed out');
        $discord->getLoop()->stop();
    });

    $discord->getLoop()->run();
    $discord->getLoop()->cancelTimer($timeout);

    return $result;
}

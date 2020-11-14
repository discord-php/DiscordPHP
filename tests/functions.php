<?php declare(strict_types=1);

const TIMEOUT = 5;

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

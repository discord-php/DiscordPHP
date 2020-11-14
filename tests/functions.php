<?php declare(strict_types=1);

const TIMEOUT = 5;

function wait(callable $callback, float $timeout = TIMEOUT)
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

        $finally = $callback($discord, $resolve);
    });

    $timeout = $discord->getLoop()->addTimer($timeout, function () use ($discord, &$timedOut) {
        $timedOut = true;
        $discord->getLoop()->stop();
    });

    $discord->getLoop()->run();
    $discord->getLoop()->cancelTimer($timeout);

    if (is_callable($finally)) {
        $finally();
    }

    if ($timedOut) {
        throw new \Exception('Timed out');
    }

    return $result;
}

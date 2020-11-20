<?php

declare(strict_types=1);

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

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

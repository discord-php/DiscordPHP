<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

include __DIR__.'/../vendor/autoload.php';

//class RedisPsr16 extends \Symfony\Component\Cache\Psr16Cache {}

// Load local .env into environment if present (simple loader, no extra deps)
$envPath = __DIR__.'/../.env';
if (file_exists($envPath)) {
    $env = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // strip surrounding quotes
        if (strlen($value) > 1 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// ReactPHP runs the event loop once more when PHP exits, if it was never run. The test clients have
// pending work that never finishes (a mock client keeps trying to reach the gateway), so that last
// run would never return and the test run would hang after its results. Registered before anything
// asks for the loop, so it runs first. Tests that need the loop run it themselves through wait().
register_shutdown_function(static fn () => React\EventLoop\Loop::stop());

include __DIR__.'/functions.php';
include __DIR__.'/DiscordSingleton.php';
include __DIR__.'/DiscordTestCase.php';

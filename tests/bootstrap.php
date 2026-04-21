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

// Suppress unhandled promise rejections from mock Discord instances used in unit tests.
// Re-register on each invocation because React/Promise's set_rejection_handler() is
// consumed (reset to null) every time __destruct() calls set_rejection_handler(null).
$silenceHandler = null;
$silenceHandler = function (\Throwable $e) use (&$silenceHandler): void {
    \React\Promise\set_rejection_handler($silenceHandler);
};
\React\Promise\set_rejection_handler($silenceHandler);

//class RedisPsr16 extends \Symfony\Component\Cache\Psr16Cache {}

// Load local .env into environment if present
\Discord\Helpers\DotEnv::load(__DIR__.'/../.env');

require_once __DIR__.'/functions.php';
require_once __DIR__.'/DiscordSingleton.php';
require_once __DIR__.'/DiscordTestCase.php';

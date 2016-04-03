<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

define('CROSS', '✗');
define('TICK', '✓');

use Discord\Discord;
use Discord\Parts\User\User;

// Require Composer dependencies
include __DIR__.'/../vendor/autoload.php';

$currentTest      = 'No Test';
$currentTestStart = null;
$tests            = [];

/**
 * Prints all the run tests.
 *
 * @return void
 */
function printAllTests()
{
    global $tests;
    $response = 'Tests:'.PHP_EOL.PHP_EOL;

    foreach ($tests as $test => $passed) {
        if ($passed) {
            $response .= TICK;
        } else {
            $response .= CROSS;
        }

        $response .= " - {$test} - ".($passed ? 'Passed' : 'Failed').PHP_EOL;
    }

    echo $response;
}

/**
 * Passes the current test.
 *
 * @return void
 */
function pass()
{
    global $currentTest, $tests, $currentTestStart;

    $timeNow = microtime(true);
    echo TICK.' '.$currentTest.' - Took '.(($timeNow - $currentTestStart) * 1000).'ms'.PHP_EOL;
    $tests[$currentTest] = true;
}

/**
 * Fails the current test.
 *
 * @param string|Exception $reason The reason the test failed.
 *
 * @return void
 */
function fail($reason = 'No reason provided.')
{
    global $currentTest, $tests;

    if ($reason instanceof \Exception) {
        $reason = $reason->getMessage();
    }

    echo CROSS.' '.$currentTest.' - '.$reason.PHP_EOL;
    $tests[$currentTest] = false;

    printAllTests();
    die(1);
}

/**
 * Starts a test.
 *
 * @param string $test The test.
 *
 * @return void
 */
function startTest($test)
{
    global $currentTest, $currentTestStart;

    $currentTest      = $test;
    $currentTestStart = microtime(true);
}

/**
 * Writes to the console.
 *
 * @param string $content Content to write to the console.
 *
 * @return void
 */
function write($content)
{
    echo $content.PHP_EOL;
}

/**
 * Checks the attributes of a part.
 *
 * @param array $expectedAttributes The expected attributes of a part.
 * @param Part  $part               The part to check.
 *
 * @return void
 */
function checkAttributes($expectedAttributes, $part)
{
    foreach ($expectedAttributes as $key => $val) {
        if ($part->{$key} != $val) {
            fail("The key '{$key}' was not equal to expected value '{$val}', value was '{$part->{$key}}'.");
        }
    }
}

// get env
$token        = getenv('DISCORD_TOKEN');
$testingGuild = getenv('DISCORD_TESTING_GUILD');
$testUser     = getenv('DISCORD_TESTING_PM');

if (empty($token)) {
    fail('No token was provided.');
}

startTest('Logging in');

try {
    $discord = new Discord($token);
    $ws      = $discord->getWebSocket();
} catch (\Exception $e) {
    fail($e);
}

pass();

$ws->on('ready', function ($discord) use ($ws, $testingGuild, $testUser) {
    pass();

    $failPromise = function ($e) {
        fail($e);
    };

    $baseGuild = $discord->guilds->get('id', $testingGuild);
    $testUser = User::find($testUser);

    require_once 'channels.php';
    require_once 'user.php';

    printAllTests();
    die;
}, function (\Exception $e) {
    fail($e->getMessage());
});

$ws->run();

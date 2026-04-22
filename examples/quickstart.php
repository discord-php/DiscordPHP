<?php

/**
 * Quickstart – minimal DiscordPHP bot
 *
 * After `composer install`, edit the generated .env and set DISCORD_TOKEN, then:
 * php examples/quickstart.php
 *
 * @see guide/quickstart.rst for a full step-by-step walkthrough
 */

include __DIR__.'/../vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;

// fromEnv() loads .env automatically and throws a helpful error if it's missing
$discord = Discord::fromEnv();

$discord->onReady(function (Discord $discord) {
    echo 'Logged in as '.$discord->user->username.'!'.PHP_EOL;

    $discord->onMessage(function (Message $message) {
        if (! $message->author->bot && $message->content === '!ping') {
            $message->reply('Pong!');
        }
    });
});

$discord->run();

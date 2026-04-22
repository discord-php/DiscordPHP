<?php

/**
 * Example Bot with Discord-PHP
 *
 * When a User says "ping", the Bot will reply "pong"
 *
 * Getting a User message content requires the Message Content Privileged Intent
 * @link http://dis.gd/mcfaq
 *
 * After `composer install`, edit the generated .env and set DISCORD_TOKEN, then:
 * php examples/ping.php
 */

include __DIR__.'/../vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;

// fromEnv() loads .env automatically and throws a helpful error if it's missing
$discord = Discord::fromEnv([
    'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT, // Required to get message content, enable it on https://discord.com/developers/applications/
]);

$discord->onReady(function (Discord $discord) {
    $discord->onMessage(function (Message $message, Discord $discord) {
        if ($message->author->bot) {
            return;
        }

        if ($message->content === 'ping') {
            $message->reply('pong');
        }
    });
});

$discord->run();

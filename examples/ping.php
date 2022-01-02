<?php

/**
 * Example Bot with Discord-PHP
 *
 * When an User says "ping", the Bot will reply "pong"
 *
 * Run this example bot from main directory using command:
 * php examples/ping.php
 */

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;

// Create a $discord BOT
$discord = new Discord([
    'token' => '', // Put your Bot token here (https://discord.com/developers/applications/)
]);

// When the Bot is ready
$discord->on('ready', function (Discord $discord) {

    // Listen for messages
    $discord->on('message', function (Message $message, Discord $discord) {
        // If message is "ping" and not from a Bot
        if ($message->content == 'ping' && ! $message->author->bot) {
            // Reply with "pong"
            $message->reply('pong');
        }
    });

});

// Start the Bot
$discord->run();

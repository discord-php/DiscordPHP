<?php

/**
 * Example Bot with Discord-PHP
 *
 * When an User says "ping", the Bot will reply "pong"
 *
 * Run this example bot from main directory using command:
 * php examples/ping.php
 */

include __DIR__.'/../vendor/autoload.php';

// Import classes, install a LSP such as Intelephense to auto complete imports
use Discord\Discord;
use Discord\Parts\Channel\Message;

// Create a $discord BOT
$discord = new Discord([
    'token' => '', // Put your Bot token here from https://discord.com/developers/applications/
]);

// When the Bot is ready
$discord->on('ready', function (Discord $discord) {

    // Listen for messages
    $discord->on('message', function (Message $message, Discord $discord) {

        // If message is from a bot
        if ($message->author->bot) {
            // Do nothing
            return;
        }

        // If message is "ping"
        if ($message->content == 'ping') {
            // Reply with "pong"
            $message->reply('pong');
        }

    });

});

// Start the Bot (must be at the bottom)
$discord->run();

<?php

/**
 * Example Bot with Discord-PHP CommandClient and ReactPHP HTTP Browser 
 *
 * When a User says "@Bot discordstatus", the Bot will reply Discord service status
 *
 * @link https://reactphp.org/http/#browser
 *
 * Run this example bot from main directory using command:
 * php examples/browser.php
 */

include __DIR__.'/../vendor/autoload.php';

// Import classes, install a LSP such as Intelephense to auto complete imports

use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

use function React\Async\coroutine;

// Create a $discord BOT
$discord = new DiscordCommandClient([
    'token' => '', // Put your Bot token here from https://discord.com/developers/applications/
]);

// Create a $browser with same loop as $discord
$browser = new Browser(null, $discord->getLoop());

$discord->registerCommand('discordstatus', function (Message $message, $params) use ($browser) {
    coroutine(function (Message $message, $params) use ($browser) {
        // Ignore messages from any Bots
        if ($message->author->bot) return;

        try {
            // Make GET request to API of discordstatus.com
            $response = yield $browser->get('https://discordstatus.com/api/v2/status.json');

            assert($response instanceof ResponseInterface); // Check if request succeed

            // Get response body
            $result = (string) $response->getBody();

            // Uncomment to debug result
            //var_dump($result);

            // Parse JSON
            $discordstatus = json_decode($result);

            // Send reply about the discord status
            $message->reply('Discord status: ' . $discordstatus->status->description);
        } catch (Exception $e) { // Request failed
            // Uncomment to debug exceptions
            //var_dump($e);

            // Send reply about the discord status
            $message->reply('Unable to acesss the Discord status API :(');
        }
    }, $message, $params);
});

// Start the Bot (must be at the bottom)
$discord->run();

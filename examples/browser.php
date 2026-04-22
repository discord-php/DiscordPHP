<?php

/**
 * Example Bot with Discord-PHP CommandClient and ReactPHP HTTP Browser
 *
 * When a User says "@Bot discordstatus", the Bot will reply Discord service status
 *
 * @link https://reactphp.org/http/#browser
 *
 * Copy .env.example to .env and set DISCORD_TOKEN, then run:
 * php examples/browser.php
 */

include __DIR__.'/../vendor/autoload.php';

use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

use function React\Async\async;
use function React\Async\await;

// fromEnv() loads .env automatically and throws a helpful error if it's missing
$discord = DiscordCommandClient::fromEnv();

$browser = new Browser();

$discord->registerCommand('discordstatus', function (Message $message, $params) use ($discord, $browser) {
    async(function () use ($message, $discord, $browser) {
        if ($message->author->bot) {
            return;
        }

        try {
            $response = await($browser->get('https://discordstatus.com/api/v2/status.json'));

            assert($response instanceof ResponseInterface);

            $result = (string) $response->getBody();

            $discord->logger->debug('Browser response', ['response' => $result]);

            $discordstatus = json_decode($result);

            $message->reply('Discord status: '.$discordstatus->status->description);
        } catch (Exception $e) {
            $discord->logger->error('Browser request failed', ['exception' => $e->getMessage()]);

            $message->reply('Unable to access the Discord status API :(');
        }
    })();
});

$discord->run();

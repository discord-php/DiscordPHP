<?php
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory;
use React\Http\Browser;

require __DIR__ . '/vendor/autoload.php';

$loop = Factory::create();

$browser = new Browser($loop);

$discord = new Discord([
    'token' => '',
    'loop' => $loop,
]);

$discord->on('message', function (Message $message, Discord $discord) use ($browser) {
    if ($message->content == 'ping') {
        $message->reply("pong");
    }
});

$discord->run();
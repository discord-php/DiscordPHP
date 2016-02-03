## DiscordPHP [![Build Status](https://travis-ci.org/teamreflex/DiscordPHP.svg?branch=master)](https://travis-ci.org/teamreflex/DiscordPHP)

An API to interact with the popular text and voice service Discord.

### Special Thanks

- [Chris Boden](https://github.com/cboden) for the WebSocket client that is based off [RatchetPHP/Pawl](https://github.com/ratchetphp/Pawl)

### Todo

Todo list is available in the [`TODO.md`](TODO.md) file.

### Basic WebSocket client

```php
<?php

include 'vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;

$discord = new Discord(':email', ':password');
$websocket = new WebSocket($discord);

$websocket->on(Event::MESSAGE_CREATE, function ($message, $discord, $new) {
	echo "New message from {$message->author->username}: {$message->content}".PHP_EOL;
});

$websocket->run();
```

### Documentation

There is currently no documentation but it will be coming soon (when I get some time). If you don't know how to do something look through the code or message me on Discord (information below).

### Help

If you need any help feel free to join the [DiscordAPI Server](https://discord.gg/0SBTUU1wZTY56U7l) and ask in the `#php_discordphp` channel. Tag `@Uniquoooo` if you need any help specific to the API.

### Other Libraries

You can find a comparison and list of all other Discord libraries over at the [DiscordAPI Comparison Page](https://discordapi.com/unofficial/comparison.html) (thanks @abalabahaha!)

### Contributing

We are open to anyone contributing as long as you follow our code standards. We use PSR-4 for our autoloading standard and PSR-2 for our code formatting standard. Please, if you send in pull requests follow these standards.
## DiscordPHP [![Build Status](https://travis-ci.org/teamreflex/DiscordPHP.svg?branch=master)](https://travis-ci.org/teamreflex/DiscordPHP)

An API to interact with the popular text and voice service Discord.

### Special Thanks

- [Chris Boden](https://github.com/cboden) for the WebSocket client that is based off [RatchetPHP/Pawl](https://github.com/ratchetphp/Pawl)
- ReactPHP for the Process class which is based off [ReactPHP/Child-Process](https://github.com/reactphp/child-process)

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

I have generated documentation which can be viewed [here](https://teamreflex.github.io/DiscordPHP). The code is well documentated so feel free to read through it if you want.

If you have any questions feel free to message me on Discord which can be viewed below.

### Cache

There is caching built into the API. By default, the time to live for an item in the cache is 300 seconds (5 minutes). If you would like to change that, do the following:

```php
use Discord\Helpers\Guzzle;

Guzzle::setCacheTtl(:time);
```

If you would like to disable the cache, set the TTL to `0`.

### Help

If you need any help feel free to join the [DiscordAPI Server](https://discord.gg/0SBTUU1wZTY56U7l) and ask in the `#php_discordphp` channel. Tag `@Uniquoooo` if you need any help specific to the API.

### Other Libraries

You can find a comparison and list of all other Discord libraries over at the [DiscordAPI Comparison Page](https://discordapi.com/unofficial/comparison.html) (thanks @abalabahaha!)

### Contributing

We are open to anyone contributing as long as you follow our code standards. We use PSR-4 for our autoloading standard and PSR-2 for our code formatting standard. Please, if you send in pull requests follow these standards.
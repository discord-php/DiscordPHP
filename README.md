## DiscordPHP [![Build Status](https://travis-ci.org/teamreflex/DiscordPHP.svg?branch=master)](https://travis-ci.org/teamreflex/DiscordPHP)

An API to interact with the popular text and voice service Discord.

### Special Thanks

- [Chris Boden](https://github.com/cboden) for the WebSocket client that is based off [RatchetPHP/Pawl](https://github.com/ratchetphp/Pawl)

### Todo

Todo list is available in the [`TODO.md`](TODO.md) file.

### Notes

- If your bot is in a large number of guilds, PHP may crash because it has ran out of allocated memory. (200 guilds, 140mb memory usage and increases)
	- You can increase the allocated memory by doing `ini_set('memory_limit', '{number-of-mb}M');` at the top of your bot file. Note: Change `{number-of-mb}` to the number of megabytes.
- If a guild has more than 250 members, only online members will be available.

### How To Install

- In order to install DiscordPHP, please be sure that you have a version of PHP that is 5.5.9 or higher
- and that you have composer installed if not here's a link https://getcomposer.org
- Once you have the appropriate version of PHP and composer.
- Create a json file called `composer.json` with this content
```json
{
	"require": {
		"team-reflex/discord-php": ">=3.1.2",
		"symfony/var-dumper": ">=3.0.3"
	}
}
```
Now run `php composer.phar install` (if you have composer installed locally) (if you have composer installed globally run `composer install` instead)
this should install the dependencies
in the folder called `vendor`.

### Troubleshooting
- If you're getting problems with Guzzle when running your bot please download this [certificate](https://www.dropbox.com/s/angtnh3lqrszs6x/cacert.pem?dl=0) *put it somewhere* and put this line into your `php.ini` file `curl.cainfo = "directory to the cert\cacert.pem"`
- If you need more help then contact `@Uniquoooo` in the discordapi Server.

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

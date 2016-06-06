DiscordPHP
====
[![Build Status](https://travis-ci.org/teamreflex/DiscordPHP.svg?branch=master)](https://travis-ci.org/teamreflex/DiscordPHP)

A wrapper for the unofficial [Discord](https://discordapp.com) REST, gateway and voice APIs.

## Getting Started

### Installing DiscordPHP

DiscordPHP is installed using [Composer](https://getcomposer.org). Make sure you have installed Composer and are used to how it operates. We require a minimum PHP version of PHP 5.5.9, however it is reccomended that you use PHP 7. PHP 5.x support **will** be removed in the future.

This library has not been tested with HHVM.

1. Run `composer require team-reflex/discord-php`. This will install the lastest release.
	- If you would like, you can also install the development branch by running `composer require team-reflex/discord-php dev-develop`.
2. Include the Composer autoload file at the top of your main file:
	- `include __DIR__.'/vendor/autoload.php';`
3. Make a bot!

### Basic Example

```php
<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;

$discord = new Discord([
	'token' => 'bot-token',
]);

$discord->on('ready', function ($discord) {
	echo "Bot is ready!", PHP_EOL;

	// Listen for messages.
	$discord->on('message', function ($message, $discord) {
		echo "{$message->author->username}: {$message->content}",PHP_EOL;
	});
});

$discord->run();
```

## Notes

- This library can use a lot of RAM and PHP may hit the memory limit. To increase the memory limit, use `ini_set('memory_limit', '200M')` to increase it to 200 mb. If you would like it to be unlimited, use `ini_set('memory_limit', '-1')`.

## Documentation

At the moment, there is no documentation however it is being written at the moment.

## Contributing

We are open to contributions. However, please make sure you follow our coding standards (PSR-4 autoloading and custom styling). We use StyleCI to format our code. Our StyleCI settings can be found [here](https://github.com/teamreflex/DiscordPHP/wiki/StyleCI).

## Library Comparison

See [this chart](https://abal.moe/Discord/Libraries.html) for a feature comparison and list of other Discord API libraries.
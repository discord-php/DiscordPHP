DiscordPHP
====
[![Build Status](https://travis-ci.org/teamreflex/DiscordPHP.svg?branch=master)](https://travis-ci.org/teamreflex/DiscordPHP) [![Discord Chat](https://img.shields.io/badge/chat-Discord%20API-blue.svg)](https://discord.gg/0SBTUU1wZTX4Mjwn) [![PHP Discorders](https://img.shields.io/badge/chat-PHP%20Discord-blue.svg)](https://discord.gg/0duG4FF1ElFGUFVq)

A wrapper for the official [Discord](https://discordapp.com) REST, gateway and voice APIs.

Project is currently being revived so please check out any Github issues. The `develop` branch is the most stable at the moment.

## FAQ

1. Can I run DiscordPHP on a webserver (e.g. Apache, nginx)?
    - No, DiscordPHP will only run in CLI. If you want to have an interface for your bot you can integrate [react/http](https://github.com/ReactPHP/http) with your bot and run it through CLI.
2. PHP is running out of memory?
	- Try increase your memory limit using `ini_set('memory_limit', '-1');`.

## Getting Started

### Installing DiscordPHP

DiscordPHP is installed using [Composer](https://getcomposer.org). Make sure you have installed Composer and are used to how it operates. We require a minimum PHP version of PHP 7.0. PHP 7.1 will be required in the near future.

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

## Documentation

Raw documentation can be found in-line in the code and on the [DiscordPHP Class Reference](http://teamreflex.github.io/DiscordPHP/). More user friendly and examples will soon be coming on the [DiscordPHP Wiki](https://discordphp.readme.io/).

## Contributing

We are open to contributions. However, please make sure you follow our coding standards (PSR-4 autoloading and custom styling). Please run php-cs-fixer before opening a pull request by running `composer run-script cs`.

## License

MIT License, &copy; David Cole and other contributers 2016--present.

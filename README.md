DiscordPHP
====
[![Build Status](https://travis-ci.org/teamreflex/DiscordPHP.svg?branch=master)](https://travis-ci.org/teamreflex/DiscordPHP) [![Discord Chat](https://img.shields.io/badge/chat-Discord%20API-blue.svg)](https://discord.gg/0SBTUU1wZTX4Mjwn) [![PHP Discorders](https://img.shields.io/badge/chat-PHP%20Discord-blue.svg)](https://discord.gg/0duG4FF1ElFGUFVq)

A wrapper for the official [Discord](https://discordapp.com) REST, gateway and voice APIs. Documentation is limited at the moment so feel free to join our Discord server [PHP Discorders](https://discord.gg/0duG4FF1ElFGUFVq) for any questions relating to the library.

For testing and stability it would be greatly appreciated if you were able to add our test bot to your server. We don't store any data - the bot simply idles and does not interact with anyone and is used to test stability with large numbers of guilds. You can invite the bot [here.](https://discord.com/oauth2/authorize?client_id=157746770539970560&scope=bot)

## FAQ

1. Can I run DiscordPHP on a webserver (e.g. Apache, nginx)?
    - No, DiscordPHP will only run in CLI. If you want to have an interface for your bot you can integrate [react/http](https://github.com/ReactPHP/http) with your bot and run it through CLI.
2. PHP is running out of memory?
	- Try increase your memory limit using `ini_set('memory_limit', '-1');`.

## Getting Started

### Requirements

- PHP 7.3
	- Technically the library can run on some versions of PHP 7.2, however, no support will be given for any version lower than 7.3.
	- The requirement will be increased to PHP 7.4 so you should develop for the latest version of PHP.
- Composer
- `ext-json`
- `ext-zlib`

#### Recommended Extensions

- The latest PHP version.
- One of `ext-uv` (preferred), `ext-libev` or `evt-event` for a faster, and more performant event loop.

#### Voice Requirements

- 64-bit Linux or Darwin based OS. Voice does not run on Windows.
- `ext-sodium`
- FFmpeg

### Installing DiscordPHP

DiscordPHP is installed using [Composer](https://getcomposer.org).

1. Run `composer require team-reflex/discord-php`. This will install the lastest release.
	- If you would like, you can also install the development branch by running `composer require team-reflex/discord-php dev-master`.
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

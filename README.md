DiscordPHP
====

[![PHP Discorders](https://discord.com/api/guilds/115233111977099271/widget.png?style=banner1)](https://discord.gg/dphp)

A wrapper for the official [Discord](https://discordapp.com) REST, gateway and voice APIs. Documentation is [available here](http://discord-php.github.io/DiscordPHP), albeit limited at the moment, as well as a class reference. Feel free to ask questions in the Discord server above.

For testing and stability it would be greatly appreciated if you were able to add our test bot to your server. We don't store any data - the bot simply idles and does not interact with anyone and is used to test stability with large numbers of guilds. You can invite the bot [here.](https://discord.com/oauth2/authorize?client_id=157746770539970560&scope=bot)

This library does not handle slash commands. See [DiscordPHP-Slash](https://github.com/discord-php/DiscordPHP-Slash) for a library to handle these interactions.

## Before you start

Before you start using this Library, you **need** to know how PHP works, you need to know the language and you need to know how Event Loops and Promises work. This is a fundamental requirement before you start. Without this knowledge, you will only suffer.

## FAQ

1. Can I run DiscordPHP on a webserver (e.g. Apache, nginx)?
    - No, DiscordPHP will only run in CLI. If you want to have an interface for your bot you can integrate [react/http](https://github.com/ReactPHP/http) with your bot and run it through CLI.
2. PHP is running out of memory?
	- Try increase your memory limit using `ini_set('memory_limit', '-1');`.

## Getting Started

### Requirements

- PHP 7.4
	- We recommend PHP 8.0 as it will be the most stable and most performant.
- Composer
- `ext-json`
- `ext-zlib`

### Windows and SSL

Unfortunately PHP on Windows does not have access to the Windows Certificate Store. This is an issue because TLS gets used and as such certificate verification gets applied (turning this off is **not** an option).

You will notice this issue by your script exiting immediately after one loop turn without any errors. Unfortunately there is for some reason no error or exception.

As such users of this library need to download a [Certificate Authority extract](https://curl.haxx.se/docs/caextract.html) from the cURL website.<br>
The path to the caextract must be set in the [`php.ini`](https://secure.php.net/manual/en/openssl.configuration.php) for `openssl.cafile`.

#### Recommended Extensions

- The latest PHP version.
- One of `ext-uv` (preferred), `ext-libev` or `evt-event` for a faster, and more performant event loop.
- `ext-mbstring` if handling non-english characters.

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

Documentation can be found [here](http://discord-php.github.io/DiscordPHP). Raw documentation can be found in-line in the code and on the [DiscordPHP Class Reference](http://discord-php.github.io/DiscordPHP/).

## Contributing

We are open to contributions. However, please make sure you follow our coding standards (PSR-4 autoloading and custom styling). Please run php-cs-fixer before opening a pull request by running `composer run-script cs`.

## License

MIT License, &copy; David Cole and other contributers 2016-present.

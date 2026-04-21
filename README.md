DiscordPHP
====
[![Latest Stable Version](https://poser.pugx.org/team-reflex/discord-php/v)](https://packagist.org/packages/team-reflex/discord-php) [![Latest Unstable Version](https://poser.pugx.org/team-reflex/discord-php/v/unstable)](https://packagist.org/packages/team-reflex/discord-php) [![Total Downloads](https://poser.pugx.org/team-reflex/discord-php/downloads)](https://packagist.org/packages/team-reflex/discord-php) [![PHP Version Require](https://poser.pugx.org/team-reflex/discord-php/require/php)](https://packagist.org/packages/team-reflex/discord-php)

[![PHP Discorders](https://discord.com/api/guilds/115233111977099271/widget.png?style=banner1)](https://discord.gg/dphp)

A wrapper for the official [Discord](https://discordapp.com) REST, gateway and voice APIs. Documentation is [available here](http://discord-php.github.io/DiscordPHP), albeit limited at the moment, as well as a [class reference](https://discord-php.github.io/DiscordPHP/guide). Feel free to ask questions in the Discord server above.

For testing and stability it would be greatly appreciated if you were able to add our test bot to your server. We don't store any data - the bot simply idles and does not interact with anyone and is used to test stability with large numbers of guilds. You can invite the bot [here.](https://discord.com/oauth2/authorize?client_id=157746770539970560&scope=bot)

## Cache Interface (experimental)
> **Warning**
> This branch contains an experimental feature, do not use it in production! See [the wiki page for more information](https://github.com/discord-php/DiscordPHP/wiki/Cache-Interface) on how to set it up.

## FAQ

1. Can I run DiscordPHP on a webserver (e.g. Apache, nginx)?
    - No, DiscordPHP will only run in CLI. If you want to have an interface for your bot you can integrate [react/http](https://github.com/ReactPHP/http) with your bot and run it through CLI.
2. PHP is running out of memory?
	- Try unlimit your PHP memory using `ini_set('memory_limit', '-1');`.

## Framework Integrations

While DiscordPHP is framework-agnostic and designed to run directly in CLI environments, there are community-maintained integrations available for popular frameworks:

- **[laracord/laracord](https://github.com/laracord/laracord)** — A Laravel integration for DiscordPHP, providing service container bindings, configuration helpers, and a more Laravel-native development experience.

Laracord is maintained independently and is not part of the DiscordPHP core project.

## Getting Started

Before you start using this Library, you **need** to know how PHP, Event Loops, and Promises work. This is a fundamental requirement before you start. Without this knowledge, you will only suffer.

### Requirements

- [PHP 8.1.2](https://php.net) or higher (latest version recommended)
	- x86 (32-bit) PHP: it is recommended to have [`ext-gmp`](https://www.php.net/manual/en/book.gmp.php) enabled.
- [`ext-json`](https://www.php.net/manual/en/book.json.php)
- [`ext-zlib`](https://www.php.net/manual/en/book.zlib.php)

#### Recommended Extensions

- One of [`ext-uv`](https://github.com/amphp/ext-uv) (recommended), `ext-ev` or `ext-event` for a faster, and more performant event loop.
- [`ext-mbstring`](https://www.php.net/manual/en/book.mbstring.php) if handling non-latin characters.

#### Voice Requirements

Voice support is bundled — no separate package installation is required.

- [`ext-sodium`](https://www.php.net/manual/en/book.sodium.php) for voice encryption.

### Windows and SSL

PHP on Windows has no access to the Windows Certificate Store, so TLS connections to Discord fail silently (the script exits after one loop turn with no error) unless you provide a CA certificate bundle.

You have two supported options — pick whichever you prefer:

#### Option 1: Use WSL (recommended)

Run your bot under [Windows Subsystem for Linux](https://learn.microsoft.com/windows/wsl/install). Inside WSL, PHP uses the Linux system CA store automatically and **no SSL setup is needed**.

#### Option 2: Native Windows

Run the helper script shipped in this repo from the project root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/windows-ssl-setup.ps1
```

It downloads a fresh CA bundle to `~/.ssl/cacert.pem` (i.e. `%USERPROFILE%\.ssl\cacert.pem`) and prints the exact path. Then hook it up in one of two ways:

**a) Let DiscordPHP apply it at runtime** *(no `php.ini` edit needed)*

Pass the path as a constructor option:

```php
$discord = new Discord([
    'token'  => 'bot-token',
    'cafile' => $_SERVER['USERPROFILE'].'\\.ssl\\cacert.pem',
]);
```

Or set the `DISCORDPHP_CAFILE` environment variable once (persists for your user):

```powershell
setx DISCORDPHP_CAFILE "$HOME\.ssl\cacert.pem"
```

The Discord constructor honours the option first, then falls back to `DISCORDPHP_CAFILE`. It only sets `openssl.cafile` / `curl.cainfo` if they are not already configured, so your existing `php.ini` is never overridden.

**b) Configure PHP globally via `php.ini`**

Find your `php.ini` with `php --ini`, then add:

```ini
openssl.cafile="C:\Users\<your-user>\.ssl\cacert.pem"
curl.cainfo="C:\Users\<your-user>\.ssl\cacert.pem"
```

Restart any running PHP processes.

### Installing DiscordPHP

DiscordPHP is installed using [Composer](https://getcomposer.org).

1. Run `composer require team-reflex/discord-php`. This will install the latest stable release.
	- If you would like, you can also install the development branch by running `composer require team-reflex/discord-php dev-master`.
2. Include the Composer autoload file at the top of your main file:
	- `include __DIR__.'/vendor/autoload.php';`
3. Make a bot!

### Basic Example

After `composer install`, edit the generated `.env` and set `DISCORD_TOKEN`, then:

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;

// fromEnv() loads .env automatically and throws a clear error if it's missing
$discord = Discord::fromEnv();

$discord->onReady(function (Discord $discord) {
    echo 'Logged in as '.$discord->user->username.'!'.PHP_EOL;

    // Listen for messages (requires MESSAGE_CONTENT intent for full content)
    $discord->onMessage(function (Message $message, Discord $discord) {
        $discord->logger->info("{$message->author->username}: {$message->content}");
    });
});

$discord->run();
```

See the [quickstart guide](guide/quickstart.rst) and [examples folder](examples) for more.

## Documentation

Documentation for the latest version can be found [here](//discord-php.github.io/DiscordPHP/guide). Community contributed tutorials can be found on the [wiki](//github.com/discord-php/DiscordPHP/wiki).

## Contributing

We are open to contributions. However, please make sure you follow our coding standards (PSR-4 autoloading and custom styling). Please run Pint before opening a pull request by running `composer run-script pint`.

## License

MIT License, &copy; David Cole and other contributers 2016-present.

## Stargazers over time
[![Stargazers over time](https://starchart.cc/discord-php/DiscordPHP.svg?variant=adaptive)](https://starchart.cc/discord-php/DiscordPHP)

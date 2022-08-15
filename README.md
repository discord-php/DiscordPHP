DiscordPHP
====
[![Latest Stable Version](https://poser.pugx.org/team-reflex/discord-php/v)](https://packagist.org/packages/team-reflex/discord-php) [![Total Downloads](https://poser.pugx.org/team-reflex/discord-php/downloads)](https://packagist.org/packages/team-reflex/discord-php) [![PHP Version Require](https://poser.pugx.org/team-reflex/discord-php/require/php)](https://packagist.org/packages/team-reflex/discord-php)

[![PHP Discorders](https://discord.com/api/guilds/115233111977099271/widget.png?style=banner1)](https://discord.gg/dphp)

A wrapper for the official [Discord](https://discordapp.com) REST, gateway and voice APIs. Documentation is [available here](http://discord-php.github.io/DiscordPHP), albeit limited at the moment, as well as a class reference. Feel free to ask questions in the Discord server above.

For testing and stability it would be greatly appreciated if you were able to add our test bot to your server. We don't store any data - the bot simply idles and does not interact with anyone and is used to test stability with large numbers of guilds. You can invite the bot [here.](https://discord.com/oauth2/authorize?client_id=157746770539970560&scope=bot)

## CacheInterface experimental branch
> Warning: This branch is in development and is experimental, do not use this in production! Create issues or join our Discord for feedback and discussions.

DiscordPHP caching is powered by [react/cache](https://github.com/reactphp/cache). The Interface can be retrieved by accessing `$discord->cache` or in any repositories `$repository->cache`, e.g.

```php
$discord->users->cache->get('115233618997149700')->then(function ($user) {
    // $user is a cached Part
});
```

Albeit example, it's preferred to fetch user like usual:
```php
$discord->users->fetch('115233618997149700', true)->then(function ($user) {
    // $user fetched from Discord API will automatically update the cache
});
```

The cache interfaces are handled in [Promise](https://github.com/reactphp/promise) manner, while it may speed up when combined with [async](https://github.com/reactphp/async), it is worth to note that is not as fast as previous in-memory caching existed since DiscordPHP v5.x. The caching interface suits for those who wants to scale up their Bot and not bound to PHP memory limit nor process, at cost of the speed.

All methods deriving from `AbstractRepository` (not `Collection`) handles the cache implementation already.

Known available implementation:

### [ArrayCache](https://github.com/reactphp/cache/blob/1.x/src/ArrayCache.php)

Bundled in ReactPHP Cache, uses in-memory Array, and is already used by default.

### [FileSystem](https://github.com/WyriHaximus/reactphp-cache-filesystem)

*Does not work on Windows*

```php
use React\EventLoop\Factory as LoopFactory;
use React\Filesystem\Filesystem as ReactFilesystem;
use WyriHaximus\React\Cache\Filesystem;

$loop = LoopFactory::create();
$filesystem = ReactFilesystem::create($loop);
$cache = new Filesystem($filesystem, '/tmp/cache/discordphp/');

$discord = new Discord([
    'token' => 'bot token',
    'loop' => $loop, // note the same loop
    'cacheInterface' => $cache,
]);
```

### [Redis](https://github.com/WyriHaximus/reactphp-cache-redis)

Note the examples below uses ReactPHP-Redis v2.x

```php
use React\EventLoop\Factory;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use WyriHaximus\React\Cache\Redis;

$loop = Factory::create();
$redis = (new Clue\React\Redis\Factory($loop))->createLazyClient('localhost:6379');
$cache = new Redis($redis, 'dphp:cache:'); // prefix is "dphp:cache"

$discord = new Discord([
    'token' => 'bot token',
    'loop' => $loop, // note the same loop
    'cacheInterface' => $cache,
]);
```

### [~~Memcached~~](https://github.com/seregazhuk/php-react-cache-memcached)

*Current version is not working*

```php
use React\EventLoop\Factory;
use seregazhuk\React\Cache\Memcached\Memcached;

$loop = Factory::create();
$cache = new Memcached($loop, 'localhost:11211', 'dphp:cache:'); // prefix is "dphp:cache"

$discord = new Discord([
    'token' => 'bot token',
    'loop' => $loop, // note the same loop
    'cacheInterface' => $cache,
]);
```

By default the cache key is prefixed "react:cache" so in your Redis/Memcached you will get have the data as: `react:cache:User:115233618997149700`. You do not need to write the prefix if accessing from the Bot code, just `User.115233618997149700`. The repository prefix can be retrieved from the repository which in the case above `$discord->users->cache->key_prefix` would return `User:`, where `User` is the name of the `Part` class.

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
	- x86 (32-bit) PHP requires [`ext-gmp` extension](https://www.php.net/manual/en/book.gmp.php) enabled for handling Permissions.
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
- One of `ext-uv` (recommended), `ext-libev` or `ext-event` for a faster, and more performant event loop.
- `ext-mbstring` if handling non-english characters.
- `ext-gmp` if using 32-bit PHP.

#### Voice Requirements

- 64-bit Linux or Darwin based OS.
    - If you are running on Windows, you must be using PHP 8.0.
- `ext-sodium`
- FFmpeg

### Installing DiscordPHP

DiscordPHP is installed using [Composer](https://getcomposer.org).

1. Run `composer require team-reflex/discord-php`. This will install the latest release.
	- If you would like, you can also install the development branch by running `composer require team-reflex/discord-php dev-master`.
2. Include the Composer autoload file at the top of your main file:
	- `include __DIR__.'/vendor/autoload.php';`
3. Make a bot!

### Basic Example

```php
<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

$discord = new Discord([
    'token' => 'bot-token',
]);

$discord->on('ready', function (Discord $discord) {
    echo "Bot is ready!", PHP_EOL;

    // Listen for messages.
    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        echo "{$message->author->username}: {$message->content}", PHP_EOL;
    });
});

$discord->run();
```

See [examples folder](examples) for more.

## Documentation

Documentation can be found [here](//discord-php.github.io/DiscordPHP). Raw documentation can be found in-line in the code and on the [DiscordPHP Class Reference](//discord-php.github.io/DiscordPHP/reference).

## Contributing

We are open to contributions. However, please make sure you follow our coding standards (PSR-4 autoloading and custom styling). Please run php-cs-fixer before opening a pull request by running `composer run-script cs`.

## License

MIT License, &copy; David Cole and other contributers 2016-present.

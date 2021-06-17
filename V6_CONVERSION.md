# Version 5.x to 6.0 Conversion Guide

## PHP Version

PHP 7.4 is now required. Please update to _at least_ PHP 7.4, but we recommend PHP 8.x for the best performance.

## Options removal

The `logging`, `httpLogger` and `loggerLevel` options have been removed. Any logs that went to the HTTP logger are now sent to the default `logger`.

If you were using the `logging` option to disable logging, you can do the same by creating a null logger:

```php
<?php

use Discord\Discord;
use Monolog\Logger;
use Monolog\Handler\NullLogger;

$logger = new Logger('Logger');
$logger->pushHandler(new NullHandler());

$discord = new Discord([
    // ...
    'logger' => $logger,
]);
```

If you were using the `loggerLevel` option to change the logger level, you can do the same by creating a logger and changing the level of the handler:

```php
<?php

use Discord\Discord;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('Logger');
$logger->pushHandler(new StreamHandler('php://stdout'), Logger::DEBUG); // Change the second parameter of this function call.
$discord = new Discord([
    // ...
    'logger' => $logger,
]);
```

## Loading all members

Alongside the `loadAllMembers` option, you now must enable the `GUILD_MEMBERS` intent. You can do this by specifying the `intents` option in the options array:

```php
<?php

use Discord\Discord;
use Discord\WebSockets\Intents;

$discord = new Discord([
    // ...
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
]);
```

Note that your bot will not be able to connect to the gateway if you have not enabled this intent in the Discord developer portal.

## Presence Updates

If you use the `PRESENCE_UPDATE` event, you must enable the intent in your Discord options array:

```php
<?php

use Discord\Discord;
use Discord\WebSockets\Intents;

$discord = new Discord([
    // ...
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_PRESENCES,
]);
```

Note that your bot will not be able to connect to the gateway if you have not enabled this intent in the Discord developer portal.

## Message Replies

If you were using the `$message->reply()` function, this now returns a Discord reply rather than a 'quote'. If you want to keep the old functionality, use `$message->channel->sendMessage()`.

## Voice Client

Copied from the changelog:

- The voice client now requires at least PHP 7.4 to operate. It will not attempt to start on any version lower.
- The voice client can now run on Windows, thanks to the introduction of socker pair descriptors in PHP 8.0 (see reactphp/child-process#85). As such, PHP 8.0 is required to run the voice client on Windows.
- DCA has been rebuilt and refactored for better use with DiscordPHP. Note that the binaries have only been rebuilt for the `amd64` architecture. The following platforms are now supported:
    - Windows AMD64
    - macOS AMD64
    - Linux AMD64
    - I'm happy to support DCA for other platforms if requested. Please ensure that your platform is supported by the Go compiler, see the supported list [here](https://golang.org/doc/install/source#introduction).
- The following functions no longer return promises, rather they throw exceptions and will return void. This is because none of these functions actually did any async work, therefore promises were redundant in this situation.
    - `setSpeaking()`
    - `switchChannel()`
    - `setFrameSize()`
    - `setBitrate()`
    - `setVolume()`
    - `setAudioApplication()`
    - `setMuteDeaf()`
    - `pause()`
    - `unpause()`
    - `stop()`
    - `close()`
    - `getRecieveStream()`
- Expect a voice client refactor in a future release.

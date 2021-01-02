---
title: "Basics"
---

First step is to include the Composer autoload file.

```php
<?php

include __DIR__.'/vendor/autoload.php';
```
<br>

The Discord instance can be set up with an array of options. All are optional except for token:

```php
$discord = new Discord([
```

`token` is your Discord token. **Required**.

```php
    'token' => 'Your-Token-Here',
```

`intents` can be an array of valid intents _or_ an integer representing the intents. Default is all intents.

```php
    'intents' => [
        Intents::GUILDS, Intents::GUILD_BANS, // ...
    ],
    // or
    'intents' => 12345,
```

`loadAllMembers` is a boolean whether all members should be fetched and stored on bot start.
Loading members takes a while to retrieve from Discord and store, so default is false.

```php
    'loadAllMembers' => false,
```

`storeMessages` is a boolean whether messages received and sent should be stored. Default is false.

```php
    'storeMessages' => false,
```

`retrieveBans` is a boolean whether bans should be retrieved on bot load. Default is false.

```php
    'retrieveBans' => false,
```

`pmChannels` is a boolean whether PM channels should be stored on bot load. Default is false.

```php
    'pmChannels' => false,
```

`disabledEvents` is an array of events that will be disabled. By default all events are enabled.

```php
    'disabledEvents' => [
        Event::MESSAGE_CREATE, Event::MESSAGE_DELETE, // ...
    ],
```

`loop` is an instance of a ReactPHP event loop that can be provided to the client rather than creating a new loop.
Useful if you want to use other React components. By default, a new loop is created.

```php
    'loop' => \React\EventLoop\Factory::create(),
```

`logger` is an instance of a logger that implements `LoggerInterface`. By default, a new Monolog logger is created to print to stdout.

```php
    'logger' => new Logger('New lOgger'),
```

`loggerLevel` is the level of logging that the default logger will use. See a list of possible constants [here](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Logger.php#L33-L78). Default is `INFO` level.

```php
    'loggerLevel' => Logger::INFO,
```

`httpLogger` is an instance of a logger for the HTTP client that implements PSR `LoggerInterface`.
By default, a null logger is created, which supresses HTTP logs.

```php
    'httpLogger' => new Logger('HTTP Logger'),
```
<hr>

The following options should only be used by large bots that require sharding. If you plan to use sharding, [read up](https://discord.com/developers/docs/topics/gateway#sharding) on how Discord implements it.

`shardId` is the ID of the bot shard.

```php
    'shardId' => 0,
```

`shardCount` is the number of shards that you are using.

```php
    'shardCount' => 5,
```

```
]);
```
<hr>

Gateway events should be registered inside the `ready` event, which is emitted once when the bot first starts and has connected to the gateway.

```php
$discord->on('ready', function (Discord $discord) {
```

To register an event we use the `$discord->on(...)` function, which registers a handler.
A list of events is available [here](https://github.com/discord-php/DiscordPHP/blob/master/src/Discord/WebSockets/Event.php#L30-L75). They are described in more detail in further sections of the documentation.
All events take a callback which is called when the event is triggered, and the callback is called with an object representing the content of the event and an instance of the `Discord` client.

```php
    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        // ... handle message sent
    });
```

```
});
```
<br>

Finally, the event loop needs to be started. Treat this as an infinite loop.

```php
$discord->run();
```

<div>
If you want to stop the bot you can run:

```php
$discord->close();
```

If you want to stop the bot without stopping the event loop, the close function takes a boolean:

```php
$discord->close(false);
```
</div>

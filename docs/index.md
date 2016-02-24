## DiscordPHP

This is the documentation for DiscordPHP. Low-level documentation can be found [here.](https://teamreflex.github.io/DiscordPHP/)

### Basic

```php
<?php

include Discord\Discord;

// Create the Discord instance.
$discord = new Discord(':email', ':password');
```

This will create a Discord REST client. If you would like to handle live events (such as messages etc.), do the following:

```php
include Discord\WebSockets\WebSocket;

$ws = new WebSocket($discord);

// Do your stuff here.

$ws->run();
```

The WebSocket instance is based off Node.JS EventEmitters, so you can run `$ws->on(event, callable);` to handle an event.
## DiscordPHP

A library for the Discord API. Currently being reworked.

### Usage

```php
use Discord\Discord;

$discord = new Discord([
	'token' => 'token',
]);

$discord->on('ready', function ($discord) {
	echo "Client connected.",PHP_EOL;
});

$discord->run();
```
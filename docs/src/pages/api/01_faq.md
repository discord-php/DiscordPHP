---
title: "FAQ"
---

### `Class 'X' not found`

You most likely haven't imported the class that you are trying to use. Please check the [class reference](http://discord-php.github.io/DiscordPHP/guide/) and search for the class that you are trying to use. Add an import statement at the top of the file like shown on the right.

```php
<?php

use Discord\X;
```

If you don't want to have to import a class every time, you should look into the PHP Intelephense language server (written above) which will do automatic imports for you.

### What does "OpenAPI Preview" mean on a class, method or constant?

Discord publishes two editions of its [OpenAPI description](https://github.com/discord/discord-api-spec): a stable one, and a preview that also covers what Discord is still rolling out. DiscordPHP checks itself against both. Anything it implements from the preview alone is marked `OpenAPI Preview` in its docblock (as `@since X.Y.Z OpenAPI Preview`) and in this reference. Discord may change or remove it at any time, without notice, so do not rely on it in production. The mark is removed once Discord adds it to the stable description.

### There are less members and/or users than expected

Server members are guarded by a priviliged server intent which must be enabled in the [Discord Developer Portal](https://discord.com/developers/applications). Note that you will need to verify your bot if you use this intent and pass 100 guilds.

You also need to enable the `loadAllMembers` option in your code, as shown on the right.

```php
$discord = new Discord([
    'token' => '...',
    'loadAllMembers' => true, // Enable this option
]);
```

If you are using DiscordPHP Version 6 or greater, you need to enable the `GUILD_MEMBERS` intent as well as the `loadAllMembers` option. The shown code will enable all intents minus the `GUILD_PRESENCES` intent (which is also priviliged).

```php

$discord = new Discord([
    'token' => '...',
    'loadAllMembers' => true,
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS // Enable the `GUILD_MEMBERS` intent
])
```

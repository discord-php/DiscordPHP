---
title: "Application Commands"
---

### Application Command Permissions Update

Called with an `Overwrite` object when an application command's permissions are updated.

```php
// use Discord\Parts\Interactions\Command\Overwrite;

$discord->on(Event::APPLICATION_COMMAND_PERMISSIONS_UPDATE, function (Overwrite $overwrite, Discord $discord, Overwrite $oldOverwrite) {
    // ...
});
```

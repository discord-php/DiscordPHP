---
title: "Application Commands"
---

### Application Command Permissions Update

Called with an `CommandPermissions` object when an application command's permissions are updated.

```php
$discord->on(Event::APPLICATION_COMMAND_PERMISSIONS_UPDATE, function (CommandPermissions $commandPermission, Discord $discord, ?CommandPermissions $oldCommandPermission) {
    // ...
});
```

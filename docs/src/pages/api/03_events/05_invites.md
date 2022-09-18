---
title: "Invites"
---

Requires the `Intents::GUILD_INVITES` intent and `manage_channels` permission.

### Invite Create

Called with an `Invite` object when a new invite to a channel is created.

```php
$discord->on(Event::INVITE_CREATE, function (Invite $invite, Discord $discord) {
    // ...
});
```

### Invite Delete

Called with an object when an invite is created.

```php
$discord->on(Event::INVITE_DELETE, function (object $invite, Discord $discord) {
    if ($invite instanceof Invite) {
        // $invite was cached
    }
    // If $invite was not in cache:
    else {
        // {
        //     "channel_id": "",
        //     "guild_id": "",
        //     "code": "" // the unique invite code
        // }
    }
});
```

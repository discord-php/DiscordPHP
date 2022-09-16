---
title: "Webhooks"
---

### Webhooks Update

Called with a `Guild` and `Channel` object when a guild channel's webhooks are is created, updated, or deleted.

```php
$discord->on(Event::WEBHOOKS_UPDATE, function (object $guild, Discord $discord, object $channel) {
    if ($guild instanceof Guild && $channel instanceof Channel) {
        // Guild and Channel is present in cache
    }
    // If not present in the cache:
    else {
        // {
        //     "guild_id": "" // webhook guild ID
        //     "channel_id": "", // webhook channel ID,
        // }
    }
});
```

Requires the `Intents::GUILD_WEBHOOKS` intent.

---
title: "Webhooks"
---

### Webhooks Update

Called with a `Guild` and `Channel` object when a guild channel's webhooks are is created, updated, or deleted.

```php
$discord->on(Event::WEBHOOKS_UPDATE, function (?Guild $guild, Discord $discord, ?Channel $channel) {
    // ...
});
```

Requires the `Intents::GUILD_WEBHOOKS` intent.

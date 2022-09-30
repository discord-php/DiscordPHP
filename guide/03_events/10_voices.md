---
title: "Voices"
---

### Voice State Update

Called with a `VoiceStateUpdate` object when a member joins, leaves or moves between voice channels.

```php
// use Discord\Parts\WebSockets\VoiceStateUpdate;

$discord->on(Event::VOICE_STATE_UPDATE, function (VoiceStateUpdate $state, Discord $discord, $oldstate) {
    // ...
});
```

Requires the `Intents::GUILD_VOICE_STATES` intent.

### Voice Server Update

Called with a `VoiceServerUpdate` object when a voice server is updated in a guild.

```php
// use Discord\Parts\WebSockets\VoiceServerUpdate;

$discord->on(Event::VOICE_SERVER_UPDATE, function (VoiceServerUpdate $guild, Discord $discord) {
    // ...
});
```

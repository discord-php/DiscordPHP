---
title: "Presences"
---

### Presence Update

Called with a `PresenceUpdate` object when a member's presence is updated.

Requires the `Intents::GUILD_PRESENCES` intent. This intent is a priviliged intent, it must be enabled in your Discord Bot developer settings.

```php
$discord->on(Event::PRESENCE_UPDATE, function (PresenceUpdate $presence, Discord $discord) {
    // ...
});
```

### Typing Start

Called with a `TypingStart` object when a user starts typing in a channel.

Requires the `Intents::GUILD_MESSAGE_TYPING` intent.

```php
// use Discord\Parts\WebSockets\TypingStart;

$discord->on(Event::TYPING_START, function (TypingStart $typing, Discord $discord) {
    // ...
});
```

### User Update

Called with a `User` object when the Bot's user properties change.

```php
$discord->on(Event::USER_UPDATE, function (User $user, Discord $discord, ?User $oldUser) {
    // ...
});
```

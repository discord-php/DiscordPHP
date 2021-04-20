---
title: "Events"
---

### Message Create

Called with a `Message` object when a message is sent in a guild or private channel.
Requires the `Intents::GUILD_MESSAGES` intent.

```php
$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    // ...
});
```

### Message Update

Called with two `Message` objects when a message is updated in a guild or private channel.
The old message may be null if `storeMessages` is not enabled _or_ the message was sent before the bot was started.
Discord does not provide a way to get message update history.
Requires the `Intents::GUILD_MESSAGES` intent.

```php
$discord->on(Event::MESSAGE_UPDATE, function (Message $newMessage, Discord $discord, $oldMessage) {
    // ...
});
```

### Message Delete

Called with a `Message` object _or_ the raw payload when a message is deleted.
The `Message` object may be the raw payload if `storeMessages` is not enabled _or_ the message was sent before the bot was started.
Discord does not provide a way to get deleted messages.
Requires the `Intents::GUILD_MESSAGES` intent.

```php
$discord->on(Event::MESSAGE_DELETE, function ($message, Discord $discord) {
    if ($message instanceof Message) {
        // Message is present in cache
    }
    // If the message is not present in the cache:
    else {
        // {
        //     "id": "", // deleted message ID,
        //     "channel_id": "", // message channel ID,
        //     "guild_id": "" // channel guild ID
        // }
    }
});
```

### Message Delete Bulk

Called with a `Collection` of `Message` objects _or_ the raw payload when bulk messages are deleted.
The `Message` object may be the raw payload if `storeMessages` is not enabled _or_ the message was sent before the bot was started.
Discord does not provide a way to get deleted messages.
Requires the `Intents::GUILD_MESSAGES` intent.

```php
$discord->on(Event::MESSAGE_DELETE_BULK, function (Collection $messages, Discord $discord) {
    foreach ($messages as $message) {
        if ($message instanceof Message) {
            // Message is present in cache
        }
        // If the message is not present in the cache:
        else {
            // {
            //     "id": "", // deleted message ID,
            //     "channel_id": "", // message channel ID,
            //     "guild_id": "" // channel guild ID
            // }
        }
    }
});
```

### Message Reaction Add

Called with a `MessageReaction` object when a reaction is added to a message.
Requires the `Intents::GUILD_MESSAGE_REACTIONS` intent.

```php
$discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove

Called with a `MessageReaction` object when a reaction is removed from a message.
Requires the `Intents::GUILD_MESSAGE_REACTIONS` intent.

```php
$discord->on(Event::MESSAGE_REACTION_REMOVE, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove All

Called with a `MessageReaction` object when all reactions are removed from a message.
Note that only the fields relating to the message, channel and guild will be filled.
Requires the `Intents::GUILD_MESSAGE_REACTIONS` intent.

```php
$discord->on(Event::MESSAGE_REACTION_REMOVE_ALL, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove Emoji

Called with an object when all reactions of an emoji are removed from a message.
This event is still to be implemented.
Requires the `Intents::GUILD_MESSAGE_REACTIONS` intent.

```php
$discord->on(Event::MESSAGE_REACTION_REMOVE_EMOJI, function ($reaction, Discord $discord) {
    // {
    //     "channel_id": "",
    //     "guild_id": "",
    //     "message_id": "",
    //     "emoji": {
    //         "id": "",
    //         "name": ""
    //     }
    // }
});
```

### Channel Create

Called with a `Channel` object when a channel is created.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::CHANNEL_CREATE, function (Channel $channel, Discord $discord) {
    // ...
});
```

### Channel Update

Called with two `Channel` objects when a channel is updated.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::CHANNEL_UPDATE, function (Channel $new, Discord $discord, Channel $old) {
    // ...
});
```

### Channel Delete

Called with a `Channel` object when a channel is deleted.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::CHANNEL_DELETE, function (Channel $channel, Discord $discord) {
    // ...
});
```

### Channel Pins Update

Called with an object when the pinned messages in a channel are updated. This is not sent when a pinned message is deleted.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::CHANNEL_PINS_UPDATE, function ($pins, Discord $discord) {
    // {
    //     "guild_id": "",
    //     "channel_id": "",
    //     "last_pin_timestamp": ""
    // }
});
```

### Guild Create

Called with a `Guild` object in one of the following situations:

1. When the bot is first starting and the guilds are becoming available.
2. When a guild was unavailable and is now available due to an outage.
3. When the bot joins a new guild.

Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::GUILD_CREATE, function (Guild $guild, Discord $discord) {
    // ...
});
```

### Guild Update

Called with two `Guild` object when a guild is updated.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::GUILD_UPDATE, function (Guild $new, Discord $discord, Guild $old) {
    // ...
});
```

### Guild Delete

Called with a `Guild` object in one of the following situations:

1. The bot was removed from a guild.
2. The guild is unavailable due to an outage.

Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::GUILD_DELETE, function (Guild $guild, Discord $discord, bool $unavailable) {
    // ...
    if ($unavailable) {
        // the guild is unavailabe due to an outage
    } else {
        // the bot has been kicked from the guild
    }
});
```

### Guild Member Add

Called with a `Member` object when a member joins a guild.
Requires the `Intents::GUILD_MEMBERS` intent. This intent is a priviliged intent, it must be enabled in your Discord bot developer settings.

```php
$discord->on(Event::GUILD_MEMBER_ADD, function (Member $member, Discord $discord) {
    // ...
});
```

### Guild Member Update

Called with two `Member` objects when a member is updated in a guild. Note that the old version of the member _may_ be null if `loadAllMembers` is disabled.
Requires the `Intents::GUILD_MEMBERS` intent. This intent is a priviliged intent, it must be enabled in your Discord bot developer settings.

```php
$discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $new, Discord $discord, $old) {
    // ...
});
```

### Guild Member Remove

Called with a `Member` object when a member leaves a guild (leave/kick/ban). Note the member object may be null if `loadAllMembers` is disabled.
Requires the `Intents::GUILD_MEMBERS` intent. This intent is a priviliged intent, it must be enabled in your Discord bot developer settings.

```php
$discord->on(Event::GUILD_MEMBER_REMOVE, function (Member $member, Discord $discord) {
    // ...
});
```

### Guild Role Create

Called with a `Role` object when a role is created in a guild.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::GUILD_ROLE_CREATE, function (Role $role, Discord $discord) {
    // ...
});
```

### Guild Role Update

Called with two `Role` objects when a role is updated in a guild.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::GUILD_ROLE_UPDATE, function (Role $new, Discord $discord, Role $old) {
    // ...
});
```

### Guild Role Delete

Called with a `Role` object when a role is deleted in a guild.
Requires the `Intents::GUILDS` intent.

```php
$discord->on(Event::GUILD_ROLE_DELETE, function (Role $role, Discord $discord) {
    // ...
});
```

### Guild Integrations Update

Called with a `Guild` object when a guild's integrations are updated.
Requires the `Intents::GUILD_INTEGRATIONS` intent.

```php
$discord->on(Event::GUILD_INTEGRATIONS_UPDATE, function (Guild $guild, Discord $discord) {
    // ...
});
```

### Invite Create

Called with an `Invite` object when an invite is created.
Requires the `Intents::GUILD_INVITES` intent.

```php
$discord->on(Event::INVITE_CREATE, function (Invite $invite, Discord $discord) {
    // ...
});
```

### Invite Delete

Called with an object when an invite is created.
Requires the `Intents::GUILD_INVITES` intent.

```php
$discord->on(Event::INVITE_DELETE, function ($invite, Discord $discord) {
    // {
    //     "channel_id": "",
    //     "guild_id": "",
    //     "code": "" // the unique invite code
    // }
});
```

### Presence Update

Called with a `PresenceUpdate` object when a members presence is updated.
Requires the `Intents::GUILD_PRESENCES` intent. This intent is a priviliged intent, it must be enabled in your Discord bot developer settings.

```php
$discord->on(Event::PRESENCE_UPDATE, function (PresenceUpdate $presence, Discord $discord) {
    // ...
});
```

### Typing Start

Called with a `TypingStart` object when a member starts typing in a channel.
Requires the `Intents::GUILD_MESSAGE_TYPING` intent.

```php
$discord->on(Event::TYPING_START, function (TypingStart $typing, Discord $discord) {
    // ...
});
```

### Voice State Update

Called with a `VoiceStateUpdate` object when a member joins, leaves or moves between voice channels.
Requires the `Intents::GUILD_VOICE_STATES` intent.

```php
$discord->on(Event::VOICE_STATE_UPDATE, function (VoiceStateUpdate $state, Discord $discord) {
    // ...
});
```

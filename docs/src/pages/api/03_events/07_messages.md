---
title: "Messages"
---

> Unlike persistent messages, ephemeral messages are sent directly to the user and the Bot who sent the message rather than through the guild channel. Because of this, ephemeral messages are tied to the `Intents::DIRECT_MESSAGES`, and the message object won't include `guild_id` or `member`.

Requires the `Intents::GUILD_MESSAGES` intent for guild or `Intents::DIRECT_MESSAGES` for direct messages.

### Message Create

Called with a `Message` object when a message is sent in a guild or private channel.

```php
$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    // ...
});
```

### Message Update

Called with `Message` or `stdClass` objects when a message is updated in a guild or private channel.
The `$message` may be an instance of `stdClass` if it was partial, otherwise a `Message`.
The old message may be null if `storeMessages` is not enabled _or_ the message was sent before the Bot was started.
Discord does not provide a way to get message update history.

```php
$discord->on(Event::MESSAGE_UPDATE, function (object $message, Discord $discord, ?Message $oldMessage) {
    if ($message instanceof Message) { // Check for non partial message
        // ...
    }
});
```

### Message Delete

Called with an old `Message` object _or_ the raw payload when a message is deleted.
The `Message` object may be the raw payload if `storeMessages` is not enabled _or_ the message was sent before the Bot was started.
Discord does not provide a way to get deleted messages.

```php
$discord->on(Event::MESSAGE_DELETE, function (object $message, Discord $discord) {
    if ($message instanceof Message) {
        // $message was cached
    }
    // $message was not in cache:
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

Called with a `Collection` of old `Message` objects _or_ the raw payload when bulk messages are deleted.
The `Message` object may be the raw payload if `storeMessages` is not enabled _or_ the message was sent before the Bot was started.
Discord does not provide a way to get deleted messages.

```php
$discord->on(Event::MESSAGE_DELETE_BULK, function (Collection $messages, Discord $discord) {
    foreach ($messages as $message) {
        if ($message instanceof Message) {
            // $message was cached
        }
        // $message was not in cache:
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

## Message Reactions

Requires the `Intents::GUILD_MESSAGE_REACTIONS` intent for guild or `Intents::DIRECT_MESSAGE_REACTIONS` for direct messages.

### Message Reaction Add

Called with a `MessageReaction` object when a user added a reaction to a message.

```php
$discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove

Called with a `MessageReaction` object when a user removes a reaction from a message.

```php
$discord->on(Event::MESSAGE_REACTION_REMOVE, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove All

Called with a `MessageReaction` object when all reactions are removed from a message.
Note that only the fields relating to the message, channel and guild will be filled.

```php
$discord->on(Event::MESSAGE_REACTION_REMOVE_ALL, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove Emoji

Called with an object when all reactions of an emoji are removed from a message.
Unlike Message Reaction Remove, this event contains no users or members.

```php
$discord->on(Event::MESSAGE_REACTION_REMOVE_EMOJI, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

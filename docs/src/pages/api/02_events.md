---
title: "Events"
---

### Message Create

Called with a `Message` object when a message is sent in a guild or private channel.

```php
$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    // ...
});
```

### Message Update

Called with two `Message` objects when a message is updated in a guild or private channel.
The old message may be null if `storeMessages` is not enabled _or_ the message was sent before the bot was started.
Discord does not provide a way to get message update history.

```php
$discord->on(Event::MESSAGE_UPDATE, function (Message $newMessage, Discord $discord, Message $oldMessage) {
    // ...
});
```

### Message Delete

Called with a `Message` object _or_ the raw payload when a message is deleted.
The `Message` object may be the raw payload if `storeMessages` is not enabled _or_ the message was sent before the bot was started.
Discord does not provide a way to get deleted messages.

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

```php
$discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $reaction, Discord $discord) {
    // ...
});
```

### Message Reaction Remove

Called with a `MessageReaction` object when a reaction is removed from a message.

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
This event is still to be implemented.

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

```php
$discord->on(Event::CHANNEL_CREATE, function (Channel $channel, Discord $discord) {
    // ...
});
```

### Channel Update

Called with two `Channel` objects when a channel is updated.

```php
$discord->on(Event::CHANNEL_UPDATE, function (Channel $new, Discord $discord, Channel $old) {
    // ...
});
```

### Channel Delete

Called with a `Channel` object when a channel is deleted.

```php
$discord->on(Event::CHANNEL_DELETE, function (Channel $channel, Discord $discord) {
    // ...
});
```

### Channel Pins Update

Called with an object when the pinned messages in a channel are updated. This is not sent when a pinned message is deleted.

```php
$discord->on(Event::CHANNEL_PINS_UPDATE, function ($pins, Discord $discord) {
    // {
    //     "guild_id": "",
    //     "channel_id": "",
    //     "last_pin_timestamp": ""
    // }
});


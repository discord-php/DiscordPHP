---
title: "Channels"
---

Requires the `Intents::GUILDS` intent.

### Channel Create

Called with a `Channel` object when a new channel is created, relevant to the Bot.

```php
$discord->on(Event::CHANNEL_CREATE, function (Channel $channel, Discord $discord) {
    // ...
});
```

### Channel Update

Called with two `Channel` objects when a channel is updated.

```php
$discord->on(Event::CHANNEL_UPDATE, function (Channel $channel, Discord $discord, ?Channel $oldChannel) {
    // ...
});
```

### Channel Delete

Called with a `Channel` object when a channel relevant to the Bot is deleted.

```php
$discord->on(Event::CHANNEL_DELETE, function (Channel $channel, Discord $discord) {
    // ...
});
```

### Channel Pins Update

Called with an object when a message is pinned or unpinned in a text channel. This is not sent when a pinned message is deleted.

```php
$discord->on(Event::CHANNEL_PINS_UPDATE, function ($pins, Discord $discord) {
    // {
    //     "guild_id": "",
    //     "channel_id": "",
    //     "last_pin_timestamp": ""
    // }
});
```

> For direct messages, it only requires the `Intents::DIRECT_MESSAGES` intent.

## Threads

Requires the `Intents::GUILDS` intent.

### Thread Create

Called with a `Thread` object when a thread is created, relevant to the Bot.

```php
$discord->on(Event::THREAD_CREATE, function (Thread $thread, Discord $discord) {
    // ...
});
```

### Thread Update

Called with a `Thread` object when a thread is updated.

```php
$discord->on(Event::THREAD_UPDATE, function (Thread $thread, Discord $discord, ?Thread $oldThread) {
    // ...
});
```

### Thread Delete

Called with an old `Thread` object when a thread relevant to the Bot is deleted.

```php
$discord->on(Event::THREAD_DELETE, function (object $thread, Discord $discord) {
    if ($thread instanceof Thread) {
        // $thread was cached
    }
    // $thread was not in cache:
    else {
    // {
    //     "type": 0,
    //     "id": "",
    //     "guild_id": "",
    //     "parent_id": ""
    // }
    }
});
```

### Thread List Sync

Called when list of threads are synced.

```php
$discord->on(Event::THREAD_LIST_SYNC, function (Collection $threads, Discord $discord) {
    // ...
});
```

### Thread Member Update

Called with a Thread `Member` object when the thread member for the current Bot is updated.

```php
// use Discord\Parts\Thread\Member;

$discord->on(Event::THREAD_MEMBER_UPDATE, function (Member $threadMember, Discord $discord) {
    // ...
});
```

### Thread Members Update

Called with a `Thread` object when anyone is added to or removed from a thread. If the Bot does not have the `Intents::GUILD_MEMBERS`, then this event will only be called if the Bot was added to or removed from the thread.

```php
$discord->on(Event::THREAD_MEMBERS_UPDATE, function (Thread $thread, Discord $discord) {
    // ...
});
```

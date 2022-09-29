---
title: "Channel"
---

Channels represent a Discord channel, whether it be a direct message channel, group channel, voice channel, text channel etc.

### Properties

| name                          | type                         | description                                                                                                                                              |
| -------------------           | ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| id                            | string                       | id of the channel                                                                                                                                        |
| name                          | string                       | name of the channel                                                                                                                                      |
| type                          | int                          | type of the channel, see Channel constants                                                                                                               |
| topic                         | string                       | topic of the channel                                                                                                                                     |
| guild_id                      | string or null               | id of the guild the channel belongs to, null if direct message                                                                                           |
| guild                         | Guild or null                | guild the channel belongs to, null if direct message                                                                                                     |
| position                      | int                          | position of the channel in the Discord client                                                                                                            |
| is_private                    | bool                         | whether the message is a private direct message channel                                                                                                  |
| last_message_id               | string                       | id of the last message sent in the channel                                                                                                               |
| bitrate                       | int                          | bitrate of the voice channel                                                                                                                             |
| recipient                     | [User](#user)                | recipient of the direct message, only for direct message channel                                                                                         |
| recipients                    | Collection of [Users](#user) | recipients of the group direct message, only for group dm channels                                                                                       |
| nsfw                          | bool                         | whether the channel is set as NSFW                                                                                                                       |
| user_limit                    | int                          | user limit of the channel for voice channels                                                                                                             |
| rate_limit_per_user           | int                          | amount of time in seconds a user has to wait between messages                                                                                            |
| icon                          | string                       | channel icon hash                                                                                                                                        |
| owner_id                      | string                       | owner of the group DM                                                                                                                                    |
| application_id                | string                       | id of the group dm creator if it was via an oauth application                                                                                            |
| parent_id                     | string                       | id of the parent of the channel if it is in a group                                                                                                      |
| last_pin_timestamp            | `Carbon` timestamp           | when the last message was pinned in the channel                                                                                                          |
| rtc_region                    | string                       | Voice region id for the voice channel, automatic when set to null.                                                                                       |
| video_quality_mode            | int                          | The camera video quality mode of the voice channel, 1 when not present.                                                                                  |
| default_auto_archive_duration | int                          | Default duration for newly created threads, in minutes, to automatically archive the thread after recent activity, can be set to: 60, 1440, 4320, 10080. |

### Repositories

| name       | type                    | notes                                           |
| ---------- | ----------------------- | ----------------------------------------------- |
| overwrites | [Overwrite](#overwrite) | Contains permission overwrites                  |
| members    | VoiceStateUpdate        | Only for voice channels. Contains voice members |
| messages   | [Message](#message)     |                                                 |
| webhooks   | [Webhook](#webhook)     | Only available in text channels                 |
| threads    | [Thread](#thread)       | Only available in text channels                 |
| invites    | [Invite](#invite)       |

### Set permissions of a member or role

Sets the permissions of a member or role. Takes two arrays of permissions - one for allow and one for deny. See [Channel Permissions](#permissions) for a valid list of permissions. Returns nothing in a promise.

#### Parameters

| name  | type                               | description                            | default  |
| ----- | ---------------------------------- | -------------------------------------- | -------- |
| part  | [Member](#member) or [Role](#role) | The part to apply the permissions to   | required |
| allow | array                              | Array of permissions to allow the part | []       |
| deny  | array                              | Array of permissions to deny the part  | []       |

```php
// Member can send messages and attach files,
// but can't add reactions to message.
$channel->setPermissions($member, [
    'send_messages',
    'attach_files',
], [
    'add_reactions',
])->done(function () {
    // ...
});
```

### Set permissions of a member or role with an Overwrite

Sets the permissions of a member or role, but takes an `Overwrite` part instead of two arrays. Returns nothing in a promise.

#### Parameters

| name      | type                               | description                          | default  |
| --------- | ---------------------------------- | ------------------------------------ | -------- |
| part      | [Member](#member) or [Role](#role) | The part to apply the permissions to | required |
| overwrite | `Overwrite` part                   | The overwrite to apply               | required |

```php
$allow = new ChannelPermission($discord, [
    'send_messages' => true,
    'attach_files' => true,
]);

$deny = new ChannelPermission($discord, [
    'add_reactions' => true,
]);

$overwrite = $channel->overwrites->create([
    'allow' => $allow,
    'deny' => $deny,
]);

// Member can send messages and attach files,
// but can't add reactions to message.
$channel->setOverwrite($member, $overwrite)->done(function () {
    // ...
});
```

### Move member to voice channel

Moves a member to a voice channel if the member is already in one. Takes a [Member](#member) object or member ID and returns nothing in a promise.

#### Parameters

| name   | type                        | description        | default  |
| ------ | --------------------------- | ------------------ | -------- |
| member | [Member](#member) or string | The member to move | required |

```php
$channel->moveMember($member)->done(function () {
    // ...
});

// or

$channel->moveMember('123213123123213')->done(function () {
    // ...
});
```

### Muting and unmuting member in voice channel

Mutes or unmutes a member in the voice channel. Takes a [Member](#member) object or member ID and returns nothing in a promise.

#### Parameters

| name   | type                        | description               | default  |
| ------ | --------------------------- | ------------------------- | -------- |
| member | [Member](#member) or string | The member to mute/unmute | required |

```php
// muting a member with a member object
$channel->muteMember($member)->done(function () {
    // ...
});

// unmuting a member with a member ID
$channel->unmuteMember('123213123123213')->done(function () {
    // ...
});
```

### Creating an invite

Creates an invite for a channel. Takes an array of options and returns the new invite in a promise.

#### Parameters

Parameters are in an array.

| name                  | type   | description                                                                                                                                                                    | default   |
| --------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------- |
| max_age               | int    | Maximum age of the invite in seconds                                                                                                                                           | 24 hours  |
| max_uses              | int    | Maximum uses of the invite                                                                                                                                                     | unlimited |
| temporary             | bool   | Whether the invite grants temporary membership                                                                                                                                 | false     |
| unique                | bool   | Whether the invite should be unique                                                                                                                                            | false     |
| target_type           | int    | The type of target for this voice channel invite                                                                                                                               |           |
| target_user_id        | string | The id of the user whose stream to display for this invite, required if target_type is `Invite::TARGET_TYPE_STREAM`, the user must be streaming in the channel                 |           |
| target_application_id | string | The id of the embedded application to open for this invite, required if target_type is `Invite::TARGET_TYPE_EMBEDDED_APPLICATION`, the application must have the EMBEDDED flag |           |

```php
$channel->createInvite([
    'max_age' => 60, // 1 minute
    'max_uses' => 5, // 5 uses
])->done(function (Invite $invite) {
    // ...
});
```

### Bulk deleting messages

Deletes many messages at once. Takes an array of messages and/or message IDs and returns nothing in a promise.

#### Parameters

| name     | type                                               | description            | default |
| -------- | -------------------------------------------------- | ---------------------- | ------- |
| messages | array or collection of messages and/or message IDs | The messages to delete | default |
| reason   | string                                             | Reason for Audit Log   |         |

```php
$channel->deleteMessages([
    $message1,
    $message2,
    $message3,
    'my_message4_id',
    'my_message5_id',
])->done(function () {
    // ...
});
```

### Getting message history

Retrieves message history with an array of options. Returns a collection of messages in a promise.

#### Parameters

| name   | type                              | description                                  | default |
| ------ | --------------------------------- | -------------------------------------------- | ------- |
| before | [Message](#message) or message ID | Get messages before this message             |         |
| after  | [Message](#message) or message ID | Get messages after this message              |         |
| around | [Message](#message) or message ID | Get messages around this message             |         |
| limit  | int                               | Number of messages to get, between 1 and 100 | 100     |

```php
$channel->getMessageHistory([
    'limit' => 5,
])->done(function (Collection $messages) {
    foreach ($messages as $message) {
        // ...
    }
});
```

### Limit delete messages

Deletes a number of messages, in order from the last one sent. Takes an integer of messages to delete and returns an empty promise.

#### Parameters

| name   | type   | description                                      | default  |
| ------ | ------ | ------------------------------------------------ | -------- |
| value  | int    | number of messages to delete, in the range 1-100 | required |
| reason | string | Reason for Audit Log                             |          |


```php
// deletes the last 15 messages
$channel->limitDelete(15)->done(function () {
    // ...
});
```

### Pin or unpin  a message

Pins or unpins a message from the channel pinboard. Takes a message object and returns the same message in a promise.

#### Parameters

| name    | type                | description              | default  |
| ------- | ------------------- | ------------------------ | -------- |
| message | [Message](#message) | The message to pin/unpin | required |
| reason  | string              | Reason for Audit Log     |          |

```php
// to pin
$channel->pinMessage($message)->done(function (Message $message) {
    // ...
});

// to unpin
$channel->unpinMessage($message)->done(function (Message $message) {
    // ...
});
```

### Get invites

Gets the channels invites. Returns a collection of invites in a promise.

```php
$channel->getInvites()->done(function (Collection $invites) {
    foreach ($invites as $invite) {
        // ...
    }
});
```

### Send a message

Sends a message to the channel. Takes a message builder. Returns the message in a promise.

#### Parameters

| name    | type                           | description                | default  |
| ------- | ------------------------------ | -------------------------- | -------- |
| message | MessageBuilder                 | Message content            | required |

```php
$message = MessageBuilder::new()
    ->setContent('Hello, world!')
    ->addEmbed($embed)
    ->setTts(true);

$channel->sendMessage($message)->done(function (Message $message) {
    // ...
});
```

### Send an embed

Sends an embed to the channel. Takes an embed and returns the sent message in a promise.

#### Parameters

| name  | type            | description       | default  |
| ----- | --------------- | ----------------- | -------- |
| embed | [Embed](#embed) | The embed to send | required |

```php
$channel->sendEmbed($embed)->done(function (Message $message) {
    // ...
});
```

### Broadcast typing

Broadcasts to the channel that the bot is typing. Genreally, bots should _not_ use this route, but if a bot takes a while to process a request it could be useful. Returns nothing in a promise.

```php
$channel->broadcastTyping()->done(function () {
    // ...
});
```

### Create a message collector

Creates a message collector, which calls a filter function on each message received and inserts it into a collection if the function returns `true`. The collector is resolved after a specified time or limit, whichever is given or whichever happens first. Takes a callback, an array of options and returns a collection of messages in a promise.

#### Parameters

| name    | type     | description                           | default  |
| ------- | -------- | ------------------------------------- | -------- |
| filter  | callable | The callback to call on every message | required |
| options | array    | Array of options                      | []       |

```php
// Collects 5 messages containing hello
$channel->createMessageCollector(fn ($message) => strpos($message->content, 'hello') !== false, [
    'limit' => 5,
])->done(function (Collection $messages) {
    foreach ($messages as $message) {
        // ...
    }
});
```

#### Options

One of `time` or `limit` is required, or the collector will not resolve.

| name  | type | description                                                      |
| ----- | ---- | ---------------------------------------------------------------- |
| time  | int  | The time after which the collector will resolve, in milliseconds |
| limit | int  | The number of messages to be collected                           |

### Get pinned messages

Returns the messages pinned in the channel. Only applicable for text channels. Returns a collection of messages in a promise.

```php
$channel->getPinnedMessages()->done(function (Collection $messages) {
    foreach ($messages as $message) {
        // $message->...
    }
});
```

---
title: "Message"
---

Messages are present in channels and can be anything from a cross post to a reply and a regular message.

### Properties

| name                   | type                               | description                                                                                          |
| ---------------------- | ---------------------------------- | ---------------------------------------------------------------------------------------------------- |
| id                     | string                             | id of the message                                                                                    |
| channel_id             | string                             | id of the channel the message was sent in                                                            |
| channel                | [Channel](#channel)                | channel the message was sent in                                                                      |
| content                | string                             | content of the message                                                                               |
| type                   | int, [Message](#message) constants | type of the message                                                                                  |
| mentions               | Collection of [Users](#user)       | users mentioned in the message                                                                       |
| author                 | [Member](#member) or [User](#user) | the author of the message, a member object in a guild channel and a user object in a private channel |
| user_id                | string                             | id of the user that sent the message                                                                 |
| mention_everyone       | bool                               | whether @everyone was mentioned                                                                      |
| timestamp              | `Carbon` timestamp                 | the time the message was sent                                                                        |
| edited_timestamp       | `Carbon` timestamp or null         | the time the message was edited or null if it hasn't been edited                                     |
| tts                    | bool                               | whether text to speech was set when the message was sent                                             |
| attachments            | array                              | array of attachments                                                                                 |
| embeds                 | Collection of [Embeds](#embed)     | embeds contained in the message                                                                      |
| nonce                  | string                             | randomly generated string for client                                                                 |
| mention_roles          | Collection of [Roles](#role)       | any roles that were mentioned in the message                                                         |
| mention_channels       | Collection of [Channels](#channel) | any channels that were mentioned in the message                                                      |
| pinned                 | bool                               | whether the message is pinned                                                                        |
| reactions              | reaction repository                | any reactions on the message                                                                         |
| webhook_id             | string                             | id of the webhook that sent the message                                                              |
| activity               | object                             | current message activity, requires rich present                                                      |
| application            | object                             | application of the message, requires rich presence                                                   |
| message_reference      | object                             | message that is referenced by the message                                                            |
| flags                  | int                                | message flags, see below 5 properties                                                                |
| crossposted            | bool                               | whether the message has been crossposted                                                             |
| is_crosspost           | bool                               | whetehr the message is a crosspost                                                                   |
| suppress_emeds         | bool                               | whether embeds have been supressed                                                                   |
| source_message_deleted | bool                               | whether the source message has been deleted e.g. crosspost                                           |
| urgent                 | bool                               | whetehr message is urgent                                                                            |

### Reply to a message

Sends a "reply" to the message. Returns the new message in a promise.

#### Parameters

| name | type   | description                 |
| ---- | ------ | --------------------------- |
| text | string | text to send in the message |

```php
$message->reply('hello!')->done(function (Message $message) {
    // ...
});
```

### Crosspost a message

Crossposts a message to any channels that are following the channel the message was sent in. Returns the crossposted message in a promise.

```php
$message->crosspost()->done(function (Message $message) {
    // ...
});
```

### Reply to a message after a delay

Similar to replying to a message, also takes a `delay` parameter in which the reply will be sent after. Returns the new message in a promise.

#### Parameters

| name  | type   | description                                              |
| ----- | ------ | -------------------------------------------------------- |
| text  | string | text to send in the message                              |
| delay | int    | time in milliseconds to delay before sending the message |

```php
// <@message_author_id>, hello! after 1.5 seconds
$message->delayedReply('hello!', 1500)->done(function (Message $message) {
    // ...
});
```

### React to a message

Adds a reaction to a message. Takes an [Emoji](#emoji) object, a custom emoji string or a unicode emoji. Returns nothing in a promise.

#### Parameters

| name     | type                      | description             |
| -------- | ------------------------- | ----------------------- |
| emoticon | [Emoji](#emoji) or string | the emoji to react with |

```php
$message->react($emoji)->done(function () {
    // ...
});

// or

$message->react(':michael:251127796439449631')->done(function () {
    // ...
});

// or

$message->react('😀')->done(function () {
    // ...
});
```

### Delete reaction(s) from a message

Deletes reaction(s) from a message. Has four methods of operation, described below. Returns nothing in a promise.

#### Parameters

| name     | type                          | description                                                                                                                          |
| -------- | ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| type     | int                           | type of deletion, one of `Message::REACT_DELETE_ALL, Message::REACT_DELETE_ME, Message:REACT_DELETE_ID, Message::REACT_DELETE_EMOJI` |
| emoticon | [Emoji](#emoji), string, null | emoji to delete, require if using `DELETE_ID`, `DELETE_ME` or `DELETE_EMOJI`                                                                         |
| id       | string, null                  | id of the user to delete reactions for, required by `DELETE_ID` |

#### Delete all reactions

```php
$message->deleteReaction(Message::REACT_DELETE_ALL)->done(function () {
    // ...
});
```

#### Delete reaction by current user

```php
$message->deleteReaction(Message::REACT_DELETE_ME, $emoji)->done(function () {
    // ...
});
```

#### Delete reaction by another user

```php
$message->deleteReaction(Message::REACT_DELETE_ID, $emoji, 'member_id')->done(function () {
    // ...
});
```

#### Delete all reactions of one emoji

```php
$message->deleteReaction(Message::REACT_DELETE_EMOJI, $emoji)->done(function () {
    // ...
});
```

### Delete the message

Deletes the message. Returns nothing in a promise.

```php
$message->delete()->done(function () {
    // ...
});
```

### Edit the message

Updates the message. Takes a message builder. Returns the updated message in a promise.

```php
$message->edit(MessageBuilder::new()
    ->setContent('new content'))->done(function (Message $message) {
        // ...
    });
```

Note fields not set in the builder will not be updated, and will retain their previous value.

### Create reaction collector

Creates a reaction collector. Works similar to [Channel](#channel)'s reaction collector. Takes a callback and an array of options. Returns a collection of reactions in a promise.

#### Options

At least one of `time` or `limit` must be specified.

| name  | type         | description                                                      |
| ----- | ------------ | ---------------------------------------------------------------- |
| time  | int or false | time in milliseconds until the collector finishes                |
| limit | int or false | amount of reactions to be collected until the collector finishes |

```php
$message->createReactionCollector(function (MessageReaction $reaction) {
    // return true or false depending on whether you want the reaction to be collected.
    return $reaction->user_id == '123123123123';
}, [
    // will resolve after 1.5 seconds or 2 reactions
    'time' => 1500,
    'limit' => 2,
])->done(function (Collection $reactions) {
    foreach ($reactions as $reaction) {
        // ...
    }
});
```

### Add embed to message

Adds an embed to a message. Takes an embed object. Will overwrite the old embed (if there is one). Returns the updated message in a promise.

#### Parameters

| name  | type            | description      |
| ----- | --------------- | ---------------- |
| embed | [Embed](#embed) | the embed to add |

```php
$message->addEmbed($embed)->done(function (Message $message) {
    // ...
});
```

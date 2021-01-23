---
title: "Member"
---

Members represent a user in a guild. There is a member object for every guild-user relationship, meaning that there will be multiple member objects in the Discord client with the same user ID, but they will belong to different guilds.

A member object can also be serialised into a mention string. For example:

```php
$discord->on(Event::MESSAGE_CREATE, function (Message $message) {
    // Hello <@member_id>!
    $message->channel->sendMessage('Hello '.$message->author.'!');
});
```

### Properties

| name          | type                                  | description                                 |
| ------------- | ------------------------------------- | ------------------------------------------- |
| id            | string                                | the user ID of the member                   |
| username      | string                                | the username of the member                  |
| discriminator | string                                | the four digit discriminator of the member  |
| user          | [User](#user)                         | the user part of the member                 |
| roles         | Collection of [Roles](#role)          | roles the member is a part of               |
| deaf          | bool                                  | whether the member is deafened              |
| mute          | bool                                  | whether the member is muted                 |
| joined_at     | `Carbon` timestamp                    | when the member joined the guild            |
| guild         | [Guild](#guild)                       | the guild the member is a part of           |
| guild_id      | string                                | the id of the guild the member is a part of |
| string        | status                                | the status of the member                    |
| game          | [Activity](#activity)                 | the current activity of the member          |
| nick          | string                                | the nickname of the member                  |
| premium_since | `Carbon` timestamp                    | when the member started boosting the guild  |
| activities    | Collection of [Activities](#activity) | the current activities of the member        |

### Ban the member

Bans the member from the guild. Returns a [Ban](#ban) part in a promise.

#### Parameters

| name         | type   | description                                          |
| ------------ | ------ | ---------------------------------------------------- |
| daysToDelete | int    | number of days back to delete messages, default none |
| reason       | string | reason for the ban                                   |

```php
$member->ban(5, 'bad person')->done(function (Ban $ban) {
    // ...
});
```

### Set the nickname of the member

Sets the nickname of the member. Requires the `MANAGE_NICKNAMES` permission. Returns nothing in a promise.

#### Parameters

| name | type   | description                                         |
| ---- | ------ | --------------------------------------------------- |
| nick | string | nickname of the member, null to clear, default null |

```php
$member->setNickname('newnick')->done(function () {
    // ...
});
```

### Move member to channel

Moves the member to another voice channel. Member must already be in a voice channel. Takes a channel or channel ID and returns nothing in a promise.

#### Parameters

| name    | type                          | description                       |
| ------- | ----------------------------- | --------------------------------- |
| channel | [Channel](#channel) or string | the channel to move the member to |

```php
$member->moveMember($channel)->done(function () {
    // ...
});

// or

$member->moveMember('123451231231')->done(function () {
    // ...
});
```

### Add member to role

Adds the member to a role. Takes a role or role ID and returns nothing in a promise.

#### Parameters

| name | type                    | description                   |
| ---- | ----------------------- | ----------------------------- |
| role | [Role](#role) or string | the role to add the member to |

```php
$member->addRole($role)->done(function () {
    // ...
});

// or

$member->addRole('1231231231')->done(function () {
    // ...
});
```

### Remove member from role

Removes the member from a role. Takes a role or role ID and returns nothing in a promise.

#### Parameters

| name | type                    | description                   |
| ---- | ----------------------- | ----------------------------- |
| role | [Role](#role) or string | the role to remove the member from |

```php
$member->removeRole($role)->done(function () {
    // ...
});

// or

$member->removeRole('1231231231')->done(function () {
    // ...
});
```

### Get permissions of member

Gets the effective permissions of the member:
- When given a channel, returns the effective permissions of a member in a channel.
- Otherwise, returns the effective permissions of a member in a guild.

Returns a [role permission](#permissions) in a promise.

#### Parameters

| name    | type                        | description                                      |
| ------- | --------------------------- | ------------------------------------------------ |
| channel | [Channel](#channel) or null | the channel to get the effective permissions for |

```php
$member->getPermissions($channel)->done(function (RolePermission $permission) {
    // ...
});

// or

$member->getPermissions()->done(function (RolePermission $permission) {
    // ...
});
```

---
title: "Member"
---

Members represent a user in a guild. There is a member object for every guild-user relationship, meaning that there will be multiple member objects in the Discord client with the same user ID, but they will belong to different guilds.

A member object can also be serialised into a mention string. For example:

```php
$discord->on(Event::MESSAGE_CREATE, function (Message $message) {
    // Hello <@member_id>!
    // Note: `$message->member` will be `null` if the message originated from
    // a private message, or if the member object was not cached.
    $message->channel->sendMessage('Hello '.$message->member.'!');
});
```

### Properties

| name                         | type                                  | description                                                                                                                                              |
| ---------------------------- | ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| user                         | [User](#user)                         | the user part of the member                                                                                                                              |
| nick                         | string                                | the nickname of the member                                                                                                                               |
| avatar                       | ?string                               | The guild avatar URL of the member                                                                                                                       |
| avatar_hash                  | ?string                               | The guild avatar hash of the member                                                                                                                      |
| roles                        | Collection of [Roles](#role)          | roles the member is a part of                                                                                                                            |
| joined_at                    | `Carbon` timestamp                    | when the member joined the guild                                                                                                                         |
| deaf                         | bool                                  | whether the member is deafened                                                                                                                           |
| mute                         | bool                                  | whether the member is muted                                                                                                                              |
| pending                      | ?string                               | whether the user has not yet passed the guild's Membership Screening requirements                                                                        |
| communication_disabled_until | `?Carbon`                             | when the user's timeout will expire and the user will be able to communicate in the guild again, null or a time in the past if the user is not timed out |
| id                           | string                                | the user ID of the member                                                                                                                                |
| username                     | string                                | the username of the member                                                                                                                               |
| discriminator                | string                                | the four digit discriminator of the member                                                                                                               |
| displayname                  | string                                | nick/username#discriminator                                                                                                                              |
| guild                        | [Guild](#guild)                       | the guild the member is a part of                                                                                                                        |
| guild_id                     | string                                | the id of the guild the member is a part of                                                                                                              |
| string                       | status                                | the status of the member                                                                                                                                 |
| game                         | [Activity](#activity)                 | the current activity of the member                                                                                                                       |
| premium_since                | `Carbon` timestamp                    | when the member started boosting the guild                                                                                                               |
| activities                   | Collection of [Activities](#activity) | the current activities of the member                                                                                                                     |

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

Sets the nickname of the member. Requires the `MANAGE_NICKNAMES` permission or `CHANGE_NICKNAME` if changing self nickname. Returns nothing in a promise.

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

### Timeout member

Times out the member in the server. Takes a carbon or null to remove. Returns nothing in a promise.

#### Parameters

| name                         | type               | description                      |
| ---------------------------- | ------------------ | -------------------------------- |
| communication_disabled_until | `Carbon` or `null` | the time for timeout to lasts on |

```php
$member->timeoutMember(new Carbon('6 hours'))->done(function () {
    // ...
});

// to remove
$member->timeoutMember()->done(function () {
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

### Get guild specific avatar URL

Gets the server-specific avatar URL for the member. Only call this function if you need to change the format or size of the image, otherwise use `$member->avatar`. Returns a string.

#### Parameters

| name   | type   | description                                                                    |
| ------ | ------ | ------------------------------------------------------------------------------ |
| format | string | format of the image, one of png, jpg or webp, default webp and gif if animated |
| size   | int    | size of the image, default 1024                                                |

```php
$url = $member->getAvatarAttribute('png', 2048);
echo $url; // https://cdn.discordapp.com/guilds/:guild_id/users/:id/avatars/:avatar_hash.png?size=2048
```

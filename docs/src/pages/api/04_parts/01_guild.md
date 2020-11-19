---
title: "Guild"
---

Guilds represent Discord 'servers'.

### Repositories

| name     | type                | notes                                                                        |
| -------- | ------------------- | ---------------------------------------------------------------------------- |
| channels | [Channel](#channel) |                                                                              |
| members  | [Member](#member)   | May not contain offline members, see the [`loadAllMembers` option](#basics). |
| roles    | [Role](#role)       |                                                                              |
| bans     | [Ban](#ban)         |                                                                              |
| invites  | [Invite](#invite)   |                                                                              |
| emojis   | [Emoji](#emoji)     |                                                                              |

### Creating a role

Shortcut for `$guild->roles->save($role);`. Takes an array of parameters for a role and returns a role part in a promise.

#### Parameters

| name        | type    | description                  | default               |
| ----------- | ------- | ---------------------------- | --------------------- |
| name        | string  | Role name                    | new role              |
| color       | integer | RGB color value              | 0                     |
| permissions | string  | Bitwise value of permissions | @everyone permissions |
| hoist       | bool    | Hoisted role?                | false                 |
| mentionable | bool    | Mentionable role?            | false                 |

```php
$guild->createRole([
    'name' => 'New Role',
    // ...
])->done(function (Role $role) {
    // ...
});
```

### Transferring ownership of guild

Transfers the ownership of the guild to another member. The bot must own the guild to be able to transfer ownership. Takes a member object or a member ID and returns nothing in a promise.

#### Parameters

| name   | type                | description                 |
| ------ | ------------------- | --------------------------- |
| member | Member or member ID | The member to get ownership |

```php
$guild->transferOwnership($member)->done(...);
// or
$guild->transferOwnership('member_id')->done(...);
```

### Unbanning a member with a User or user ID

Unbans a member when passed a `User` object or a user ID. If you have the ban object, you can do `$guild->bans->delete($ban);`. Returns nothing in a promise.

#### Parameters

| name    | type              | description       |
| ------- | ----------------- | ----------------- |
| user_id | `User` or user ID | The user to unban |

```php
$guild->unban($user)->done(...);
// or
$guild->unban('user_id')->done(...);
```


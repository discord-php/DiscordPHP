---
title: "Permissions"
---

There are two types of permissions - channel permissions and role permissions. They are represented by their individual classes, but both extend the same abstract permission class.

### Properties

| name                    | type | description            |
| ----------------------- | ---- | ---------------------- |
| bitwise                 | int  | bitwise representation |
| create\_instant\_invite | bool |                        |
| manage\_channels        | bool |                        |
| view\_channel           | bool |                        |
| manage\_roles           | bool |                        |

The rest of the properties are listed under each permission type, all are type of `bool`.

### Methods

#### Fetch effective permissions from Discord

Added in DiscordPHP 10.71.0; requires `discord-php/http` 10.9.9 or newer.

`Guild::fetchMemberPermissions($member)` and `Guild::fetchRolePermissions($role)`
fetch Discord's calculated guild-level permissions. Member results cascade all
the member's roles, including `@everyone`. Role results combine the selected role
with `@everyone`, rather than returning only `$role->permissions`.

`Channel::fetchMemberPermissions($member, $implicit = true)` and
`Channel::fetchRolePermissions($role, $implicit = true)` fetch permissions after
channel overwrites. Threads expose the same methods and use their own channel ID.
Discord requires the calling bot to have `VIEW_CHANNEL`. Pass `false` as the
second argument to omit [implicit permissions](https://docs.discord.com/developers/topics/permissions#implicit-permissions);
the default includes them. DM and group DM channels reject these operations.

All four methods return a promise resolving to a `RolePermission` snapshot,
preserving the full set of known guild and channel flags. Members may be passed
as a `Member`, `User` or user ID string; roles as a `Role` or role ID string.
The methods need no cached roles or overwrites and do not replace cached role,
member or interaction permission data. API errors reject the promise.

```php
use Discord\Parts\Permissions\RolePermission;

$guild->fetchMemberPermissions('user_id')->then(function (RolePermission $permissions) {
    var_dump($permissions->manage_guild);
}, function (Throwable $error) {
    echo $error->getMessage(), PHP_EOL;
});

$channel->fetchRolePermissions('role_id', false)->then(function (RolePermission $permissions) {
    var_dump($permissions->view_channel);
    echo (string) $permissions, PHP_EOL; // Decimal bitfield.
}, function (Throwable $error) {
    echo $error->getMessage(), PHP_EOL;
});
```

`$member->getPermissions($channel)` and `getBotPermissions()` still calculate
permissions synchronously from local caches. Use the fetch methods when you need
Discord's calculation without relying on a complete cache.

These endpoints follow [Discord API documentation PR #8695](https://github.com/discord/discord-api-docs/pull/8695),
reviewed at `a668a51dc1c8177684a7b6fa5a36b698ed655845`. The prose PR remains open,
but all four operations are in Discord's stable OpenAPI description at
`4e75626a4cedfbc397d66f5491c79a9a62a71fd5`. The prose may change; offline request
tests do not establish live endpoint availability.

#### Get all valid permissions

Returns a list of valid permissions, in key value form. Static method.

```php
var_dump(ChannelPermission::getPermissions());
// [
//     'priority_speaker' => 8,
//     // ...
// ]
```

### Channel Permission

Represents permissions for text, voice, and stage instance channels.

#### Text Channel Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `add_reactions`
- `send_messages`
- `send_tts_messages`
- `manage_messages`
- `embed_links`
- `attach_files`
- `read_message_history`
- `mention_everyone`
- `use_external_emojis`
- `manage_webhooks`
- `use_application_commands`
- `manage_threads`
- `create_public_threads`
- `create_private_threads`
- `use_external_stickers`
- `send_messages_in_threads`

#### Voice Channel Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `priority_speaker`
- `stream`
- `connect`
- `speak`
- `mute_members`
- `deafen_members`
- `move_members`
- `use_vad`
- `manage_events`
- `create_events`
- `use_embedded_activities` was `start_embedded_activities`

#### Stage Instance Channel Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `connect`
- `mute_members`
- `deafen_members`
- `move_members`
- `request_to_speak`
- `manage_events`
- `create_events`

### Role Permissions

Represents permissions for roles.

#### Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `kick_members`
- `ban_members`
- `administrator`
- `manage_guild`
- `view_audit_log`
- `view_guild_insights`
- `change_nickname`
- `manage_nicknames`
- `manage_emojis_and_stickers`
- `moderate_members`

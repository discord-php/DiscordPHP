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
| manage\_webhooks        | bool |                        |

The rest of the properties are listed under each permission type, all are type of `bool`.

### Methods

#### Get all valid permissions

Returns a list of valid permissions, in key value form. Static method.

```php
var_dump(ChannelPermission::getPermissions());
// [
//     'priority_speaker' => 0x123,
//     // ...
// ]
```

### Channel Permission

Represents permissions for voice and text channels.

#### Voice Channel Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `manage_webhooks`
- `priority_speaker`
- `stream`
- `connect`
- `speak`
- `mute_members`
- `deafen_members`
- `move_members`
- `use_vad`

#### Text Channel Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `manage_webhooks`
- `add_reactions`
- `send_messages`
- `send_tts_messages`
- `manage_messages`
- `embed_links`
- `attach_files`
- `read_message_history`
- `mention_everyone`
- `use_external_emojis`

### Role Permissions

Represents permissions for roles.

#### Permissions

- `create_instant_invite`
- `manage_channels`
- `view_channel`
- `manage_roles`
- `manage_webhooks`
- `kick_members`
- `ban_members`
- `administrator`
- `manage_guild`
- `view_audit_log`
- `view_guild_insights`
- `change_nicknames`
- `manage_nicknames`
- `manage_emojis`

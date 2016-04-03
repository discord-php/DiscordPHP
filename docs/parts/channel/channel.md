## Channel

### Attributes

- `id`
	- `int`
	- The identifier for the channel.
- `name`
	- `string`
	- The channel name.
- `type`
	- `string`
	- Either `text` or `voice`.
- `topic`
	- `string`
	- The channel topic (if it is a `text` channel).
- `guild_id`
	- `int`
	- The ID of the guild that the channel belongs to.
- `position`
	- `int`
	- The position of the channel on the sidebar.
- `is_private`
	- `bool`
	- Whether the channel is a PM channel.
- `last_message_id`
	- `int`
	- The ID of the last message sent.
- `permission_overwrites`
	- `array`
	- An array of permission overwrites.
	- See `overwrites`.
- `message_count`
	- `int`
	- How many messages have been sent in the channel.

- `guild`
	- [`Guild`](../guild/guild.md)
	- The guild that the Channel belongs to.
- `messages`
	- [`Collection`](../../collection.md)
	- A Collection of [`Messages`](message.md)
- `invites`
	- [`Collection`](../../collection.md)
	- A Collection of [`Invites`](../guild/invite.md) that are made for this channel.
- `overwrites`
	- [`Collection`](../../collection.md)
	- A Collection of [`Overwrites`](overwrite.md)

### Functions

All functions that are available for the `Channel` object can be viewed [here.](https://teamreflex.github.io/DiscordPHP/classes/Discord.Parts.Channel.Channel.html#method_setPermissions)

### Actions

- Findable: Yes
- Creatable: Yes
- Editable: Yes
- Deletable: Yes
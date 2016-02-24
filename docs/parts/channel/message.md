## Message

### Attributes

- `id`
	- `int`
	- The ID of the message.
- `channel_id`
	- `int`
	- The ID of the [`Channel`](channel.md) that the message was sent in.
- `content`
	- `string`
	- The content of the message.
- `mentions`
	- `array`
	- An array of mentions.
	- **Note:** Will be removed soon as mentions have changed.
- `mention_everyone`
	- `bool`
	- Whether the message was sent with an `@everyone` tag.
- `timestamp`
	- `Carbon\Carbon`
	- The time that the message was sent.
- `edited_timestamp`
	- `Carbon\Carbon`
	- The time that the message was last edited.
- `tts`
	- `bool`
	- Whether the message was sent with `/tts`.
- `attachments`
	- `array`
	- An array of attachments that the message was sent with.
- `embeds`
	- `array`
	- An array of embeds that the message was sent with.
- `nonce`
	- `int`
	- A value that was sent with the creation of the message.

- `channel`
	- [`Channel`](channel.md)
	- The channel that the message was sent with.
	- **Note:** This Channel instance only contains the ID of the channel (so that you can send messages to it), if you need more information use `full_channel` from below.
- `full_channel`
	- [`Channel`](channel.md)
	- The channel that the message was sent with (with extra information such as name etc.)
- `author`
	- [`User`](../user/user.md)
	- The user that sent the message.

### Functions

All functions that are available for the `Message` object can be viewed [here.](https://teamreflex.github.io/DiscordPHP/classes/Discord.Parts.Channel.Message.html#method_reply)

### Actions

- Findable: No
- Creatable: Yes
- Editable: Yes
- Deletable: Yes
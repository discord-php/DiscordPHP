---
title: "Voices"
---

### Voice State Update

Called with a `VoiceStateUpdate` object when a member joins, leaves or moves between voice channels.

```php
// use Discord\Parts\WebSockets\VoiceStateUpdate;

$discord->on(Event::VOICE_STATE_UPDATE, function (VoiceStateUpdate $state, Discord $discord, $oldstate) {
    // ...
});
```

Requires the `Intents::GUILD_VOICE_STATES` intent.

### Voice Server Update

Called with a `VoiceServerUpdate` object when a voice server is updated in a guild.

```php
// use Discord\Parts\WebSockets\VoiceServerUpdate;

$discord->on(Event::VOICE_SERVER_UPDATE, function (VoiceServerUpdate $guild, Discord $discord) {
    // ...
});
```

---

## Voice Channel Status and Start Time

Voice channel status and voice session start time are ephemeral fields that are not present on the channel object. Apps can request these fields from the gateway using the Request Channel Info command and will receive a Channel Info event in response.

### Request Channel Info

Send an opcode `43` payload to request ephemeral channel data for a guild. The server will reply with a `Channel Info` event containing the requested fields for channels in the guild.

Example payload:

```json
{
    "guild_id": "613425648685547541",
    "fields": ["status", "voice_start_time"]
}
```

Available fields:

- `status` — short status string set for the voice channel.
- `voice_start_time` — timestamp for when the voice session started.

### Events

- `Voice Channel Status Update` — fired when a channel's `status` changes.
- `Voice Channel Start Time Update` — fired when a voice session's start time changes.

### Audit Log

Two audit log event types track status changes:

- `VOICE_CHANNEL_STATUS_UPDATE` (`192`) — contains `status` in the Optional Audit Entry Info.
- `VOICE_CHANNEL_STATUS_DELETE` (`193`).


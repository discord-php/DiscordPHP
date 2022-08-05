---
title: "Events"
---

Events are payloads sent over the socket to a client that correspond to events in Discord.

All gateway events are enabled by default and can be individually disabled using `disabledEvents` options.
Most events also requires the respective Intents enabled (as well privileged ones enabled from [Developers Portal](https://discord.com/developers/applications)) regardless the enabled event setting.

To listen on gateway events, use the event emitter callback and `Event` name constants.
Some events are internally handled by the library and may not be registered a listener:

- `Event::READY` (not to be confused with `'ready'`)
- `Event::RESUMED`
- `Event::GUILD_MEMBERS_CHUNK`

If you are an advanced user, you may listen to those events before internally handled with the library by parsing the 'raw' dispatch event data.

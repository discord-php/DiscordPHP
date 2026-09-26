---
title: "Events"
---

Events are payloads sent over the socket to a client that correspond to events in Discord.

All gateway events are enabled by default and can be individually disabled using `disabledEvents` options.
Most events also requires the respective Intents enabled (as well privileged ones enabled from [Developers Portal](https://discord.com/developers/applications)) regardless the enabled event setting.

To listen on gateway events, use the event emitter callback and `Event` name constants.
Some events are internally handled by the library and may not be registered a listener:

- `Event::READY`
- `Event::RESUMED`
- `Event::GUILD_MEMBERS_CHUNK`

If you are an advanced user, you may listen to those events before internally handled with the library by parsing the 'raw' dispatch event data.

### Rate Limited

Called with a `RateLimited` object when Discord refuses a request sent over the gateway, such as `Discord::requestGuildMembers()`, for sending too many. It says which opcode was refused, how many seconds to wait before sending it again, and, for member requests, which guild and nonce it was for.

The client sends its own member requests again by itself, after the wait, so `loadAllMembers` still completes. Requests you send are yours to retry.

```php
// use Discord\Parts\WebSockets\RateLimited;

$discord->on(Event::RATE_LIMITED, function (RateLimited $rateLimit, Discord $discord) {
    $discord->getLoop()->addTimer($rateLimit->retry_after, fn () => $discord->requestGuildMembers($rateLimit->guild_id, ['query' => '', 'limit' => 0]));
});
```

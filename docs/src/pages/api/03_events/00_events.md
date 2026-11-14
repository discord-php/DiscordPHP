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

### Gateway transport observation

`$discord->isConnected()` reports an open Gateway WebSocket while the client is not
closing. It does **not** imply authentication, completed `init`, complete caches, REST
availability or uninterrupted continuity. Continue using `init` for initial startup and
`reconnected` for recovery. Session observation can remain application-local using the
existing `raw` READY/RESUMED and INVALID_SESSION/RECONNECT packets; raw callbacks still
run before internal handling. Keep generation counters, validation and retry policy in
the application.

Register `disconnected` before running the loop so it includes loss before `init`:

```php
$discord->on('disconnected', function (int $code, string $reason, Discord $discord) {
    // isConnected() is already false. Invalidate application work here.
});
```

This synchronous event fires once per connected transport, including normal and critical
closes. State becomes false before cleanup; notification follows timer cleanup and
existing reconnect scheduling. On explicit `close()`, state becomes false before the
socket close request; notification follows shutdown work, including `closed` and stopping
the loop if requested. Synchronous or delayed socket callbacks do not duplicate it.
Explicit shutdown code/reason are locally requested, not server-confirmed. Failed
connection attempts that never opened a transport do not emit it.

Listeners run inline: keep them short and avoid throwing. Exceptions propagate, but
cannot skip the close cleanup or shutdown work preceding notification. This does not
change existing reconnect policy and does not describe every possible session change.

---
name: runtime-bootstrap-keeper
description: >-
  Maintain Discord.php runtime bootstrapping, startup options, event loop,
  gateway connection, reconnection, member chunking, cache configuration,
  and process lifecycle. Use when touching Discord.php, startup wiring,
  intents, or root repositories.
---

# Skill: runtime-bootstrap-keeper

Use this skill when work touches `src/Discord/Discord.php`, startup options, loop setup, cache configuration, gateway connection behavior, readiness, member chunking, reconnect logic, or root repositories.

This is not generic PHP skill. This is the orchestrator-lifecycle guard. Load it when changing how the client boots, what dependencies it wires, how it connects to Discord's gateway, or how it reaches the ready state.

## Goal

Keep `Discord` as the single bootstrapping orchestrator:

- resolves all user-facing options centrally through `OptionsResolver`
- wires infrastructural dependencies once during construction
- connects to the gateway eagerly from the constructor
- delegates loop start to a one-liner `run()` method
- maintains lifecycle flags that the rest of the system depends on
- owns root repositories as long-lived properties on the `Client` part
- manages the ready flow: guild backfill → member chunking → init emission

## Read in this order

1. `src/Discord/Discord.php` — the entire orchestrator
2. `src/Discord/WebSockets/Handlers.php` — handler registry for gateway events
3. `src/Discord/WebSockets/Event.php` — event constant definitions
4. `src/Discord/Factory/Factory.php` — part/repository factory, created once here
5. `src/Discord/Parts/User/Client.php` — the `Client` part that holds root repos
6. `README.md` — public-facing construction examples
7. `guide/basics.rst` — user-facing lifecycle guide

Do not start by reading individual event classes. Understand the wiring in `Discord.php` first, then follow references outward.

## Core contract

`Discord` is the application entry point. It is not a service class, not a controller, not a request handler. It is a long-running CLI process orchestrator built on ReactPHP. The entire class rests on these guarantees:

- `__construct()` does real work: resolves options, creates HTTP client, creates Factory, creates `Client` part, registers handlers, and calls `connectWs()` to open the gateway
- `run()` only starts the ReactPHP event loop — it must stay a one-liner
- the class assumes CLI SAPI — a web-server warning is logged if `php_sapi_name()` is not `cli` or `micro`
- `Factory` is created exactly once and shared across the entire system
- root repositories (`guilds`, `users`, `private_channels`, `emojis`, `sounds`, `sticker_packs`, `lobbies`) live on the `Client` part, proxied through `Discord::__get()`

If a change violates any of these, the runtime becomes unpredictable.

## Options and intent resolution

All user-facing configuration is resolved in `resolveOptions()` using Symfony's `OptionsResolver`. This is the single normalization point for the entire client.

### Key options

| Option | Type | Default | Purpose |
| --- | --- | --- | --- |
| `token` | string | required | Bot authentication token |
| `loop` | `LoopInterface` | `Loop::get()` | ReactPHP event loop |
| `logger` | `LoggerInterface\|null` | Monolog stdout | PSR-3 logger |
| `intents` | `array\|int` | `Intents::getDefaultIntents()` | Gateway intents bitmask |
| `loadAllMembers` | `bool\|array` | `false` | Enable member chunking for all or specific guild IDs |
| `disabledEvents` | `array` | `[]` | Event names to skip in handler registry |
| `storeMessages` | `bool` | `false` | Whether to cache messages |
| `retrieveBans` | `bool\|array` | `false` | Whether to fetch bans on guild create |
| `cache` | `array\|CacheConfig\|CacheInterface` | `[AbstractRepository::class => null]` | Cache backend for repositories |
| `collection` | `string` | `Collection::class` | Collection class implementing `ExCollectionInterface` |
| `useTransportCompression` | `bool` | `true` | zlib-stream transport compression |
| `usePayloadCompression` | `bool` | `true` | Per-payload compression |
| `socket_options` | `array` | `[]` | Passed to React socket connector |
| `large_threshold` | `int\|null` | `null` | Guild member threshold for "large" guilds |
| `presence` | `array\|null` | `null` | Initial presence payload |

### Intent folding

If `intents` is passed as an array of intent constants, `resolveOptions()` folds them into a single bitmask with bitwise OR. This happens once at resolution time — the rest of the system sees only the integer.

If `loadAllMembers` is enabled, the resolver enforces that `GUILD_MEMBERS` intent is included, throwing `IntentException` otherwise.

### Cache normalization

The `cache` option normalizer converts bare `CacheInterface` or `CacheConfig` values into the canonical `[AbstractRepository::class => $config]` array shape. The default `null` value means `LegacyCacheWrapper` (in-memory) is used. External cache backends are treated as experimental and logged as warnings.

## Construction vs run() lifecycle

### What `__construct()` does (in order)

1. Validates x86 GMP extension requirement
2. Calls `resolveOptions()` — all normalization and validation happens here
3. Stores `$token`, `$loop`, `$logger`, `$cacheConfig`, `$collectionClass`
4. Checks CLI SAPI — logs critical warning if web context detected
5. Creates `SocketConnector` and `Connector` (WebSocket factory)
6. Creates `Handlers` instance — the gateway event handler registry
7. Warns if critical events (`GUILD_CREATE`, `GUILD_DELETE`, `RESUMED`, `READY`, `GUILD_MEMBERS_CHUNK`) are in `disabledEvents`
8. Removes disabled event handlers from registry
9. Creates `Http` client with React driver
10. Creates `Factory` — the single part/repository factory for the system
11. Creates `Client` part via factory — this is where root repositories are born
12. Stores compression settings
13. Calls `connectWs()` — initiates the gateway connection

### What `run()` does

```php
public function run(): void
{
    $this->loop->run();
}
```

Nothing else. The loop was already wired during construction. `run()` simply unblocks the event loop. Do not add bootstrap logic here.

### Why this matters

The eager constructor means the system is fully wired before `run()` is called. User code that registers event listeners between `new Discord(...)` and `$discord->run()` works because the loop has not started yet but all infrastructure is ready. Moving connection logic to `run()` would break this contract.

## Gateway connection and reconnection

### `connectWs()`

Retrieves the gateway URL via `setGateway()`, then opens a WebSocket connection through `$this->wsFactory`. On success, calls `handleWsConnection()`. On failure, calls `handleWsConnectionFailed()` which retries after 5 seconds.

`setGateway()` calls the REST endpoint `Endpoint::GATEWAY_BOT` to get the gateway URL and session start limit. The URL is decorated with `v`, `encoding`, and optional `compress=zlib-stream` query parameters in `buildParams()`. If a `resume_gateway_url` was received from a prior READY, that is used instead.

### `handleWsConnection()`

Sets `$this->connected = true`, initializes the payload rate counter (120/60s limit with 5 reserved for heartbeats), and registers `message`, `close`, and `error` listeners on the WebSocket.

### `handleWsClose()`

Cancels heartbeat and payload timers. If `$this->closing` is true, returns silently. If the close code is a critical op code (checked via `Op::getCriticalCloseCodes()`), does not reconnect. Otherwise, sets `$reconnecting = true` and calls `connectWs()` after a 2-second delay.

### `handleHello()`

Received when the gateway connection is established. Calls `setupHeartbeat()` with the server-provided interval, then calls `identify()`.

### `identify()` vs `resume()`

`identify()` sends `OP_IDENTIFY` with token, properties, intents, shard info, and presence. `resume()` sends `OP_RESUME` with token, session ID, and last sequence number. The gateway decides which path to use after reconnection via `handleInvalidSession()`.

### `handleInvalidSession()`

If the session is resumable (`$data->d` is true), attempts `resume()`. Otherwise, calls `identify()` after a 2-second delay.

### Heartbeat

`setupHeartbeat()` creates a periodic timer at the server-specified interval. Each `heartbeat()` call sends `OP_HEARTBEAT` with the current sequence number and starts a guard timer. If no `HEARTBEAT_ACK` arrives within one heartbeat interval, the connection is closed with code 1001, triggering reconnect.

## Ready flow

The ready sequence is the most delicate part of the bootstrap. It ensures all guilds are loaded and optionally chunked before userland code runs.

### Step 1: `handleReady()`

Receives the READY payload. If `$this->reconnecting` is true, it skips full re-parsing and just emits `reconnected`. Otherwise:

1. Stores `resume_gateway_url` for future reconnects
2. Fills the `Client` part with user data and stores `sessionId`
3. Iterates `content->guilds` and processes each through `GuildCreate::handle()`
4. Tracks unavailable guilds

If all guilds are immediately available, calls `ready()` directly. Otherwise, sets up temporary `GUILD_CREATE` and `GUILD_DELETE` listeners to track when unavailable guilds become available. A 60-second safety timer also triggers `ready()` as fallback.

### Step 2: `setupChunking()`

Called when all guilds are available. If `loadAllMembers` is false, calls `ready()` immediately. Otherwise, starts a periodic 5-second timer that calls `checkForChunks()`.

### Step 3: `checkForChunks()`

Drains the `$largeGuilds` array in batches of 50, sending `OP_REQUEST_GUILD_MEMBERS` for each. Guilds are added to `$largeGuilds` by `addLargeGuild()`, which is called from `GuildCreate` event handling. If `loadAllMembers` is an array of guild IDs, only those guilds are chunked.

### Step 4: `handleGuildMembersChunk()`

Processes incoming member chunk payloads, caching each `Member` into the guild's member repository and each `User` into the top-level user repository. When a guild's cached member count reaches its `member_count`, it is removed from `$largeSent`. When `$largeSent` is empty, `ready()` fires.

### Step 5: `ready()`

Guarded by `$emittedInit` — runs at most once. Initializes the voice manager if the class exists. Emits `init` (the primary ready event). Emits deprecated `ready` with a warning. Drains `$unparsedPackets` — dispatch events that arrived before init are processed here.

## Root repositories

Root repositories are not properties of `Discord` itself. They live on the `Client` part (`src/Discord/Parts/User/Client.php`), which defines `$repositories`:

- `guilds` → `GuildRepository`
- `users` → `UserRepository`
- `private_channels` → `PrivateChannelRepository`
- `emojis` → `EmojiRepository`
- `sounds` → `SoundRepository`
- `sticker_packs` → `StickerPackRepository`
- `lobbies` → `LobbyRepository`

`Discord::__get()` proxies all unknown property accesses to `$this->client`, so `$discord->guilds` resolves to `$this->client->guilds`. A small allowlist (`loop`, `options`, `logger`, `http`, `application_commands`) is intercepted before delegation.

These repositories are long-lived for the entire process lifetime. They are the system of record for cached Discord state.

## Factory and dependency wiring

`Factory` is created once in the constructor: `$this->factory = new Factory($this)`. It holds a reference back to the `Discord` instance and provides `part()` and `repository()` methods for typed construction.

The `Client` part is the first object the factory creates: `$this->client = $this->factory->part(Client::class, [])`. After that, all part and repository creation flows through this single factory instance.

Callers access the factory via `$discord->getFactory()` or the convenience `$discord->factory($class, $data, $created)` method. Do not create ad-hoc factory instances elsewhere — the system assumes one factory tied to one client.

## Cache configuration

The `cacheConfig` property is an array keyed by repository class names. `getCacheConfig($repository_class)` looks up the config for a specific repository class, falling back to `AbstractRepository::class` as the default key.

Default behavior: `null` config means `LegacyCacheWrapper` is used (in-memory `WeakMap`-backed cache). External cache backends (`React\Cache\CacheInterface`, `Psr\SimpleCache\CacheInterface`) are wrapped in `CacheConfig` and logged as experimental.

Per-repository cache configs can be set by passing an array keyed by repository class names in the `cache` option. This allows different repositories to use different cache backends.

## Logging and lifecycle flags

### Lifecycle flags

| Property | Type | Meaning |
| --- | --- | --- |
| `$connected` | `bool` | WebSocket is currently open |
| `$closing` | `bool` | Client is intentionally shutting down |
| `$reconnecting` | `bool` | Client is in reconnect cycle |
| `$emittedInit` | `bool` | `init` event has been emitted at least once |
| `$reconnectCount` | `int` | Number of reconnections since boot |
| `$seq` | `int` | Current gateway sequence number |
| `$sessionId` | `string` | Current gateway session ID |

These flags are checked across connection, dispatch, and close handlers. Changing their semantics or lifecycle ordering can silently break reconnection or event delivery.

### Logger

If no logger is provided, `resolveOptions()` creates a Monolog instance writing to stdout at Debug level with `LineFormatter`. The logger is stored on `$this->logger` and exposed via `getLogger()` and `__get('logger')`.

## Dispatch routing

`processWsMessage()` decodes JSON payloads and routes by opcode. `OP_DISPATCH` goes to `handleDispatch()`, which checks `Handlers` for a registered event class. If found, it instantiates the handler, runs `handle()` as a coroutine, and emits the event name with resolved data. If not found, it checks a static map of internal handlers (`VOICE_STATE_UPDATE`, `VOICE_SERVER_UPDATE`, `RESUMED`, `READY`, `GUILD_MEMBERS_CHUNK`).

Before `$emittedInit` is true, non-critical dispatch events are queued in `$unparsedPackets` rather than processed immediately. Only `GUILD_CREATE` and `GUILD_DELETE` are allowed through during the ready flow.

## Smells

Stop if you see:

- bootstrap logic added to `run()` instead of `__construct()`
- a second `Factory` instance created anywhere
- option validation scattered outside `resolveOptions()`
- intent bitmask manipulation after options are resolved
- `$emittedInit` checked or set from outside `Discord.php`
- critical events (`GUILD_CREATE`, `GUILD_DELETE`, `READY`, `GUILD_MEMBERS_CHUNK`) removed without understanding the ready flow
- synchronous blocking in production code paths (acceptable only in tests)
- web-framework concepts (request/response, middleware, controllers) in `Discord.php`
- reconnect logic that does not respect `Op::getCriticalCloseCodes()`
- cache config manipulation after construction
- root repositories created outside the `Client` part

## Checklist before commit

- `resolveOptions()` handles any new option with proper type, default, and normalization
- `__construct()` wiring order preserved — options first, then HTTP, then factory, then client, then connect
- `run()` remains a one-liner
- lifecycle flags (`$connected`, `$closing`, `$reconnecting`, `$emittedInit`) not repurposed
- ready flow sequence (guild backfill → chunking → init) not broken
- critical events not silently disabled
- `connectWs()` → `handleWsConnection()` → `handleHello()` → `identify()` chain intact
- heartbeat timer and ACK guard logic preserved
- `$unparsedPackets` drain in `ready()` still works
- reconnect path (`handleWsClose()` → delay → `connectWs()`) handles critical vs non-critical close codes
- tests/docs updated if public behavior changed

## Bottom line

`Discord.php` is the single eager orchestrator for a long-running CLI process. It resolves options once, wires dependencies once, connects to the gateway eagerly, and starts the loop on demand. Keep it centered on that job — do not turn it into a service locator, request handler, or lazy-boot framework.

---
name: helpers-and-infra-keeper
description: >-
  Work with DiscordPHP's infrastructure utilities — CacheWrapper, CacheConfig,
  BigInt, Multipart, Endpoint::bind URL templates, Collection base class, and
  domain Exceptions. Use when changing cache behavior, REST endpoint routing,
  file uploads, big-integer ID math, or adding/modifying exceptions.
---

# Skill: helpers-and-infra-keeper

Use this skill when work touches `src/Discord/Helpers/*`, `src/Discord/Exceptions/*`, `Endpoint::bind()` patterns, or the external `Collection` / `discord-php/http` packages.

These are cross-cutting infrastructure utilities. They have no domain logic, but nearly every other layer depends on them.

## Read in this order

1. `src/Discord/Helpers/CacheWrapper.php` — PSR-16 + React cache bridge
2. `src/Discord/Helpers/CacheConfig.php` — cache options (compression, sweep, TTL)
3. `src/Discord/Helpers/LegacyCacheWrapper.php` — fallback for older cache implementations
4. `src/Discord/Helpers/BigInt.php` — 32-bit PHP GMP polyfill for snowflake IDs and permission bitfields
5. `src/Discord/Helpers/Multipart.php` — multipart/form-data builder for file uploads
6. `src/Discord/Repository/AbstractRepository.php` — how repositories consume `CacheWrapper` and `$endpoints`
7. `src/Discord/Discord.php` — `options['cache']` wiring to `CacheWrapper`/`CacheConfig`

## Cache infrastructure

### Three-class hierarchy

| Class | Role |
| --- | --- |
| `CacheConfig` | Value object holding cache options passed in `Discord::__construct()` `options['cache']` |
| `CacheWrapper` | Main wrapper: bridges React's async cache (PSR-16 + react/cache) to repository storage. Handles compression if configured. |
| `LegacyCacheWrapper` | Default when no external cache is configured. Wraps an in-memory store. Used by default. |

**Rule:** repositories do not interact with cache stores directly. They always go through `CacheWrapper`. Do not bypass it.

### When to use CacheConfig

If a feature needs configurable cache behavior (TTL, compression, sweep interval), add it as a property on `CacheConfig`. Do not scatter cache tuning across individual repositories.

### Cache coherence rule

When a REST write succeeds, the cache write must happen in the same promise chain — not after a refetch. This is enforced by convention, not framework. Break the chain and you get stale cache.

## Endpoint::bind() — URL template pattern

All REST endpoints are defined as `Endpoint` constants in the external `discord-php/http` package:

```php
use Discord\Http\Endpoint;

protected $endpoints = [
    'get'    => Endpoint::CHANNEL,
    'update' => Endpoint::CHANNEL,
    'delete' => Endpoint::CHANNEL,
];
```

Routes with parameters use `:param` placeholders. Binding happens via `Endpoint::bind($endpoint, $params)` where `$params` is a key-value map matching the placeholders.

**Rule:** never build URL strings by hand. Always use `Endpoint::bind()` or assign a named `Endpoint` constant. Raw string concatenation bypasses rate-limit bucket identification.

### Finding endpoint constants

Browse `vendor/discord-php/http/src/Endpoint.php` for available constants. If a Discord API endpoint is missing, add it to the external package — do not define it inline in DiscordPHP source.

## Collection base class

All repositories extend `Collection` from `discord-php-helpers/collection`. Key behaviors inherited:

- discriminator-keyed storage (`$discrim` — defaults to `'id'`)
- typed item enforcement (`$class`)
- `get($key)`, `has($key)`, `first()`, `find(callable)`, `filter(callable)`
- JSON serialization

`AbstractRepository` adds REST/cache behavior on top. Do not re-implement collection operations in repositories — use the inherited helpers.

## BigInt

`BigInt` provides GMP-based arithmetic for environments where PHP is compiled as 32-bit, where Discord snowflake IDs and 64-bit permission bitfields overflow native integers.

Usage pattern:
```php
use Discord\Helpers\BigInt;

$perms = BigInt::bitAnd($memberPerms, $requiredPerm);
```

**Rule:** use `BigInt` for any permission flag arithmetic or snowflake comparison that could run on 32-bit PHP. Do not assume `PHP_INT_SIZE === 8`.

## Multipart

`Multipart` builds `multipart/form-data` request bodies for file attachment uploads. It is used by `MessageBuilder` and similar builders when attachments are present.

It has no cache or domain logic — treat it as a serialization helper.

## Exception hierarchy

`src/Discord/Exceptions/` contains domain-specific exceptions. Do not use generic `\RuntimeException` or `\InvalidArgumentException` when a typed exception already exists:

| Exception | When to throw |
| --- | --- |
| `IntentException` | Invalid or missing gateway intents at startup |
| `PartRequestFailedException` | A part fetch or REST operation failed |
| `InvalidOverwriteException` | A permission overwrite is malformed |
| `AttachmentSizeException` | Attachment exceeds size limit |
| `FileNotFoundException` | File path not found for upload |
| `BufferTimedOutException` | Audio buffer read timed out |
| `LibSodiumNotFoundException` | `ext-sodium` not available (voice) |
| `OpusNotFoundException` | Opus codec binary not available |
| `FFmpegNotFoundException` | FFmpeg binary not found |
| `DCANotFoundException` | DCA tool not found |

When adding a new typed exception: extend `\Exception` or the nearest domain parent, keep the name `*Exception`, and put it in `src/Discord/Exceptions/`.

## Companion surfaces

| Touching | Also inspect |
| --- | --- |
| `CacheWrapper` or `CacheConfig` | `Discord.php` `options['cache']` wiring, `AbstractRepository` cache usage, `LegacyCacheWrapper` fallback |
| `Endpoint` constants | `$endpoints` arrays in all affected repositories, rate-limit bucket behavior |
| `BigInt` | Permission parts (`Permission`, `RolePermission`, `ChannelPermission`), snowflake comparison sites |
| `Multipart` | `MessageBuilder` attachment handling, HTTP client request formation |
| An exception class | Callers that catch the specific type, docs/guide if it's user-facing |

## Design tripwires

- Building REST URL strings by hand instead of using `Endpoint::bind()`
- Writing to a cache store directly inside a repository, bypassing `CacheWrapper`
- Re-implementing collection iteration helpers that `Collection` already provides
- Using `PHP_INT_SIZE === 8` as a safe assumption for permission arithmetic — use `BigInt`
- Throwing `\RuntimeException` when a typed domain exception already exists
- Adding domain logic (Discord resource rules) to any class in `Helpers/` or `Exceptions/`
- Putting new audio stream or voice crypto helpers in `Helpers/` — those belong in `Voice/*` or the external voice package

## Reference files

- `src/Discord/Helpers/CacheWrapper.php` — main cache bridge
- `src/Discord/Helpers/CacheConfig.php` — cache options value object
- `src/Discord/Helpers/LegacyCacheWrapper.php` — default in-memory cache
- `src/Discord/Helpers/BigInt.php` — 32-bit PHP GMP polyfill
- `src/Discord/Helpers/Multipart.php` — file upload form builder
- `src/Discord/Repository/AbstractRepository.php` — how CacheWrapper and Endpoint are consumed
- `src/Discord/Exceptions/IntentException.php` — representative typed exception
- `src/Discord/Exceptions/PartRequestFailedException.php` — REST failure exception
- `vendor/discord-php/http/src/Endpoint.php` — all REST endpoint constants

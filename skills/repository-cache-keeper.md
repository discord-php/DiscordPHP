# Skill: repository-cache-keeper

Use this skill when work touches `src/Discord/Repository/**/*`.

This is cache-and-REST boundary skill. Load it when a change affects how parts are collected, fetched, persisted, cached, or routed to endpoints.

## Goal

Keep repositories as:

- typed collections
- route-aware REST wrappers
- cache coordinators
- owning persistence boundary for a part family

Not as service dumping ground.

## Read in this order

1. `src/Discord/Repository/AbstractRepository.php`
2. `src/Discord/Repository/AbstractRepositoryTrait.php`
3. `src/Discord/Parts/PartTrait.php` sections for `getRepositoryAttributes()`, `getCreatableAttributes()`, `getUpdatableAttributes()`
4. `src/Discord/Helpers/CacheWrapper.php` and cache config code if cache behavior matters
5. Representative repos:
   - `src/Discord/Repository/GuildRepository.php`
   - `src/Discord/Repository/Guild/ChannelRepository.php`
   - `src/Discord/Repository/Channel/MessageRepository.php`
   - `src/Discord/Repository/Interaction/GlobalCommandRepository.php`
   - `src/Discord/Repository/Guild/GuildCommandRepository.php`
6. The owning part for the repository you are touching

## Core contract

Every repository centers on same shape:

- extends `AbstractRepository`
- sets `$class` to the concrete part family
- usually sets `$endpoints`
- may override `$discrim`
- receives `$vars` route context from parent part or caller
- stores typed items in collection semantics
- persists through HTTP
- mirrors authoritative state into cache

If a method no longer feels like typed collection + route-aware persistence, it probably belongs elsewhere.

## Meaning of common properties

### `$class`

Concrete part family allowed in this repository. If wrong, hydration, type checks, and cache writes all become unsafe.

### `$endpoints`

Route templates keyed by operation name. Typical keys:

- `all`
- `get`
- `create`
- `update`
- `delete`

Not every repository supports every operation. Missing endpoint is an intentional capability statement.

### `$vars`

Route-binding context. This is how nested repos know where they live:

- guild repositories get `guild_id`
- channel repositories get `channel_id`
- some repos also need `application_id`, `message_id`, or other parent route pieces

When repo behavior looks wrong, inspect parent part `getRepositoryAttributes()` first.

### `$discrim`

Collection key field, usually `id`. Only change when payload family genuinely keys on a different field.

### `$cache`

Per-repository cache wrapper. It is not optional frosting. Repo methods should keep it coherent with in-memory `items`.

## Core methods and what they mean

### `create(array|object $attributes = [], bool $created = false)`

Builds local typed part using repository context plus attributes. No REST call. No remote persistence. Use it for drafts or hydration helpers.

### `save(Part $part, ?string $reason = null)`

Generic persistence path:

- POST when `$part->created` is false
- PATCH when `$part->created` is true
- uses part-provided repository attributes and creatable/updatable attributes
- updates cache with returned part

Part-level `save()` may wrap or redirect this when semantics demand it.

### `delete($part, ?string $reason = null)`

Remote delete plus cache removal. Accepts part or identifier. Keep typed return contract.

### `fetch(string $id, bool $fresh = false)`

Resolve from memory, then cache, then REST unless forced fresh. This is one of main async entry points. Keep it Promise-based.

### `fresh(Part $part, array $queryparams = [])`

Refresh a known created part from REST and update cache.

### `freshen(array $queryparams = [])`

Bulk refresh repository contents from REST. This is repo-wide sync tool, not a one-item fetch.

## How cache and collection semantics fit together

Repositories are hybrid objects:

- collection-like for iteration, filtering, `get()`, `find()`, `first()`, `last()`
- async cache-aware for `cacheGet()`, `cachePull()`, `fetch()`, `freshen()`

That dual nature is intentional. Do not flatten repository into only async map or only plain collection.

### Important behavior to preserve

- in-memory `items` can hold real parts, `WeakReference`, or null placeholders
- cache wrapper may outlive in-memory strong references
- repo methods often try memory first, then cache, then REST
- successful REST mutations should refresh cache state immediately

## Endpoint routing rules

### Use `Endpoint` and bound vars

Repository methods should bind route vars through `Endpoint`/`Endpoint::bind()`, not manual string interpolation spread across code.

### Parent context comes from parts

If child repository starts losing route info, fix parent part `getRepositoryAttributes()` or repo constructor normalization, not random callers.

### Constructor normalization is acceptable

If a repo needs to clean or enrich vars for route correctness, constructor override is fine. Example patterns already exist:

- `MessageRepository` unsets `thread_id` for thread message routing
- `GuildCommandRepository` injects bot application id

## When to add domain-specific repository methods

Add repo-specific methods when operation is broader than generic CRUD and naturally belongs to collection/route boundary.

Good examples:

- leaving a guild from `GuildRepository`
- previewing a guild from `GuildRepository`
- creating guild channels from `ChannelRepository`

Bad examples:

- permission-heavy semantic operation better expressed as `$part->save()` or `$part->someAction()`
- userland convenience that only repackages one line and adds no domain meaning

## Ownership boundary: part vs repository

### Part owns

- semantic meaning of operation
- permission checks
- special-case routing based on object meaning
- public high-level API surface

### Repository owns

- endpoint selection
- request method and payload dispatch
- cache persistence
- typed hydration from HTTP response

If code needs to know "is this current member or generic member", "is this webhook message or normal message", or "does bot need manage_messages here", that usually belongs on part side first.

## New repository playbook

1. Choose concrete part family and set `$class`
2. Set `$endpoints`
3. Decide `$discrim`
4. Verify parent route vars required
5. Verify parent part `getRepositoryAttributes()`
6. Add domain-specific methods only if generic CRUD not enough
7. Inspect gateway events that should populate this repo
8. Add docblock `@method` helpers if family uses them

## Existing patterns worth copying

### Thin specialization

Some repos are intentionally small:

- `GlobalCommandRepository`
- `MessageRepository`

That is okay. Not every repo needs custom methods.

### Constructor route correction

If nested route shape is tricky, normalize vars in constructor instead of forcing every caller to know internals.

### Cache-first reads

Methods like `fetch()` and `get()` preserve performance by preferring in-memory/cache before REST. Keep that bias unless correctness requires bypass.

## Gateway interaction rules

Repositories are often updated from gateway events, not only from their own REST methods. When changing repository keying or cache semantics, inspect matching event handlers. If repository expectations change but event cache writes do not, the repo will look "randomly stale".

Common families to cross-check:

- guild/channel/message/thread create/update/delete events
- interaction entitlements or command-related caches
- member/user/presence caches

## Smells

Stop if you see:

- raw REST payloads returned where typed part should be returned
- new route vars threaded manually through many call sites
- repo mutating part semantics that belong in part `save()`
- cache not updated after successful REST mutation
- in-memory `items` updated but cache wrapper ignored, or reverse
- parent part and repo disagree on route context
- generic service/helper class added to do what repository already does

## Checklist before commit

- `$class`, `$endpoints`, `$discrim`, `$vars` still describe repo correctly
- parent `getRepositoryAttributes()` still supplies needed route vars
- create/save/delete/fetch semantics remain typed and Promise-based
- cache and in-memory items stay aligned
- any special methods belong here semantically
- related gateway event handlers still populate repo coherently
- docs/tests updated if public behavior changed

## Bottom line

Repository code in this repo should feel boring in right way: typed, route-aware, cache-coherent, and predictable. If a repository starts making domain decisions or returning untyped transport data, pull it back to its real job.

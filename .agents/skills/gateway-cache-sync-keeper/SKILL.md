---
name: gateway-cache-sync-keeper
description: >-
  Maintain gateway event handlers — payload hydration, cache mutation, event
  return shapes, and handler registration. Use when touching
  WebSockets/Events, Handlers.php, or Event.php.
---

# Skill: gateway-cache-sync-keeper

Use this skill when work touches:

- `src/Discord/WebSockets/Handlers.php`
- `src/Discord/WebSockets/Event.php`
- `src/Discord/WebSockets/Events/*`

This is event-to-cache coherence skill. Load it when changing gateway dispatch behavior, event return shapes, or cache mutation.

## Goal

Keep gateway handling as real-time state sync layer:

- dispatch payloads become typed parts
- related repositories get updated immediately
- emitted event payloads stay semantically useful
- caches for users, members, channels, messages, threads, guilds stay coherent

## Read in this order

1. `src/Discord/WebSockets/Event.php`
2. `src/Discord/WebSockets/Handlers.php`
3. Matching part/repository families
4. Representative events:
   - `src/Discord/WebSockets/Events/MessageCreate.php`
   - `src/Discord/WebSockets/Events/MessageUpdate.php`
   - `src/Discord/WebSockets/Events/GuildCreate.php`
   - `src/Discord/WebSockets/Events/GuildDelete.php`
   - `src/Discord/WebSockets/Events/InteractionCreate.php`
   - `src/Discord/WebSockets/Events/ThreadListSync.php`
   - `src/Discord/WebSockets/Events/GuildMemberAdd.php`

## Core contract

Event classes in this repo are not dumb payload forwarders. They:

- interpret transport payloads
- instantiate correct typed parts, often via runtime `TYPES` maps
- update owning repositories and related caches
- warm user/member caches from nested payloads
- preserve secondary invariants like counters or `last_message_id`
- return semantic values used by userland listeners

If handler only hydrates one object and ignores related caches, change is probably incomplete.

## Three files usually move together

When adding or changing event family, inspect all three:

1. `src/Discord/WebSockets/Event.php` — constant
2. `src/Discord/WebSockets/Handlers.php` — registration
3. `src/Discord/WebSockets/Events/<Name>.php` — implementation

If one changes and the others do not, make sure that is intentional.

## Event base helpers to use

### `cacheUser(object $userdata)`

Use when payload contains user data. Keeps top-level user cache current.

### `cacheMember(MemberRepository $members, array $memberdata)`

Use when payload contains guild member data. Keeps guild member cache current. Some event families override details for performance reasons, like `InteractionCreate`.

Do not manually duplicate user/member cache update logic unless event family has special latency or shape constraints.

## Hydration rules

### Prefer typed parts immediately

Use factory plus subtype maps when payload family is polymorphic:

- channels from `Channel::TYPES`
- interactions from `Interaction::TYPES`
- components from component `TYPES`

Do not push raw `stdClass` payloads deep into caches when repo already has typed part model.

### Create vs update vs delete semantics matter

- create events usually instantiate new parts and set them into owning repo
- update events often clone old cached part, mutate current part, and return both new and old values
- delete events often pull from cache and return removed typed part plus extra status

Preserve those shapes unless you have strong compatibility reason to change them.

## Coherence rules by family

### Message events

These are easy to break because they carry many side effects:

- store or update message in channel/thread repo when configured
- keep `last_message_id` current
- handle message-content intent limits correctly
- update author/mention/interacting user caches
- preserve old/new payload behavior on update

If you change `MessageCreate` or `MessageUpdate`, inspect:

- message cache behavior
- intent-gated content handling
- DM vs guild channel routing
- thread message counting or parent lookup logic

### Guild create/delete/update families

Guild events often do bulk cache work:

- guild create populates guild repo plus member, user, voice state, thread, stage instance, scheduled event, sound caches
- guild delete must preserve unavailable-vs-removed semantics

Do not treat guild create as "just one guild part". It is bootstrap event for several nested repositories.

### Thread events

Threads are child collections hanging off parent channels. Thread handlers usually must:

- locate parent channel
- update parent `threads` repo
- possibly update thread member state
- preserve thread counters or metadata

### Interaction events

Interaction handlers are latency-sensitive. They still must:

- hydrate correct interaction subtype
- cache resolved users/members
- avoid unnecessarily slow cache paths
- invoke registered command handlers/autocomplete flow

## Event return-shape rules

Userland listens to emitted values. Keep return shapes stable and meaningful.

Common patterns already used:

- `MessageCreate` returns new `Message`
- `MessageUpdate` returns `[newOrData, oldMessage]`
- `GuildDelete` returns `[$guildPartOrData, $unavailableFlag]`

Before changing a return shape, inspect:

- existing event listeners in examples/tests/docs
- parity with sibling events in same family
- whether old state is required for consumers

## Cache path playbook

When changing event behavior, walk this checklist:

1. What is primary part produced by payload?
2. Which repository should own it?
3. Which parent or child repositories also need update?
4. Which related users or members appear in payload?
5. Do counters, last IDs, or status fields need sync?
6. What should event listener receive back?

If you cannot answer each, handler is not fully understood yet.

## Registration playbook

When adding new dispatch type:

1. add constant to `Event.php`
2. add handler registration to `Handlers.php`
3. create event class
4. choose emitted event aliases if handler needs alternatives
5. ensure any runtime option that disables events still behaves sensibly

## Partial payload and intent rules

Gateway payloads are not always full objects.

Examples:

- message update may be partial
- message content may be missing without `MESSAGE_CONTENT` intent
- interaction-related payloads may favor speed over full cache warmup
- guild create member lists vary with guild size and enabled intents

Do not blindly overwrite cached richer state with partial payload unless code already accounts for missing fields.

## Performance rules

- prefer payload data over avoidable REST fetches
- reuse cache lookups instead of broad scans when possible
- but preserve correctness for parent-child lookup when repo shape demands it
- interaction path should avoid slow cache resolution that delays response handling

## Smells

Stop if you see:

- event class added without handler registration
- cache update for primary part but not related repositories
- raw payload stuffed in cache when typed part exists
- user/member data in payload ignored even though later code depends on cache
- update event overwriting rich cached fields with partial sparse payload
- return shape change with no thought for existing listener contract

## Checklist before commit

- event constant and handler registration aligned
- correct subtype hydration path used
- primary and related repositories updated
- user/member caches updated where payload carries them
- partial/intents behavior preserved
- event return shape still intentional and compatible
- tests/docs/examples checked if public listener contract changed

## Bottom line

Gateway events are cache-synchronization code. Treat them like consistency-critical logic, not like thin adapters. One missed repository update can make the whole object graph lie.

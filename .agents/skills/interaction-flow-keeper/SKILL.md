---
name: interaction-flow-keeper
description: >-
  Maintain interaction flow — Interaction typing, resolved data caching,
  command routing, autocomplete, modal responses, and interaction builders.
  Use when touching Interactions or slash command handling.
---

# Skill: interaction-flow-keeper

Use this skill when work touches:

- `src/Discord/Parts/Interactions/*`
- `src/Discord/WebSockets/Events/InteractionCreate.php`
- application command registration or autocomplete routing
- modal or component interaction response behavior

This is typed-interaction flow skill. Load it when work affects how interactions are received, interpreted, routed, or answered.

## Goal

Keep interactions as a coherent pipeline:

- gateway payload enters through `InteractionCreate`
- payload is typed into correct interaction subclass
- resolved data warms caches
- registered command callbacks route correctly
- response builders and helpers stay aligned with interaction protocol

## Read in this order

1. `src/Discord/Parts/Interactions/Interaction.php`
2. `src/Discord/WebSockets/Events/InteractionCreate.php`
3. `src/Discord/Helpers/RegisteredCommand.php`
4. Related request data parts under `src/Discord/Parts/Interactions/Request/*`
5. Outbound builders:
   - `src/Discord/Builders/CommandBuilder.php`
   - `src/Discord/Builders/ModalBuilder.php`
   - component builders when component responses matter
6. Command repositories:
   - `src/Discord/Repository/Interaction/GlobalCommandRepository.php`
   - `src/Discord/Repository/Guild/GuildCommandRepository.php`

## Core contract

Interaction flow in this repo has two distinct sides:

### Inbound

- gateway dispatch handled by `InteractionCreate`
- `Interaction::TYPES` chooses concrete subtype
- nested data is exposed as typed interaction request parts
- resolved users/members/entitlements are cached

### Outbound

- interaction response helpers or builders produce protocol-correct payloads
- commands are declared with command builders/repositories
- autocomplete and modal flows use interaction response types and component/modals builders

Do not mix inbound part modeling with outbound builder modeling.

## Typing rules

### `Interaction::TYPES` is central

If Discord adds or repo adds support for new interaction type:

1. update `Interaction::TYPES`
2. add subtype class if needed
3. inspect `InteractionCreate`
4. inspect builder/response paths if outbound behavior changes too

### Resolved data should not stay loose if typed part exists

`Interaction` and its request data parts should expose:

- users
- members
- channels
- roles
- options

through typed parts or typed collections where repo already has models.

## `InteractionCreate` rules

This handler is latency-sensitive and still responsible for cache correctness.

Preserve these patterns:

- type interaction early
- cache resolved users
- cache resolved members when guild/member context exists
- avoid unnecessarily slow cache lookups that delay interaction handling
- cache entitlements
- route registered command execution/autocomplete callbacks

The handler already overrides some cache behavior to stay fast. Do not "clean it up" into slower generic behavior without understanding timing tradeoffs.

## Registered command routing rules

There is an interaction-specific registered command tree in `RegisteredCommand`.

Keep these facts straight:

- this is separate from prefix-command `CommandClient\Command`
- `Discord::listenCommand()` populates `application_commands`
- `InteractionCreate` routes slash/autocomplete events into that tree
- `RegisteredCommand` supports nested subcommand trees
- autocomplete uses `suggest()`
- execution uses `execute()`

If change touches application command routing, inspect both:

- `Discord::listenCommand()` path
- `InteractionCreate` callback routing path

## Resolved data and cache rules

When payload provides users or members:

- warm global user cache
- warm guild member cache when guild is present
- preserve interaction speed by preferring cheap access patterns

When payload provides entitlements:

- sync them into application entitlements repo

Do not assume interaction payload is only for immediate response. It also updates local state for later code.

## Performance rules

Interaction code has less patience for extra work than many other gateway paths.

Prefer:

- type from payload
- cache from payload
- direct route to callback

Avoid:

- broad cache scans
- avoidable REST fetches
- generic helper usage if it adds measurable delay in hot path

## Outbound response rules

### Use builders where they exist

- command definitions: `CommandBuilder`
- modals: `ModalBuilder`
- message/component payloads: message/component builders

### Keep response type constants as public vocabulary

`Interaction` defines response type constants. Use them to keep protocol semantics readable.

### Modal and component usage constraints matter

If interaction response uses components or modals:

- validate allowed component contexts
- preserve nested `type` + `data` response shape

## Boundaries to preserve

### Interaction system vs prefix command system

They are different layers:

- application commands / autocomplete / modals route through `RegisteredCommand` and interaction parts
- message-prefix commands route through `DiscordCommandClient` and `CommandClient\Command`

Do not unify them by shoving shared semantics into wrong layer. Shared ideas may exist, but execution paths are different.

### Interaction part vs message part

An interaction may reference a message, but it is not a message event. Keep response and request semantics on interaction types.

## Smells

Stop if you see:

- new interaction type added without `Interaction::TYPES` update
- resolved data left raw even though typed parts already exist
- application command routing pushed into prefix-command code
- autocomplete logic mixed with normal execute flow in confusing ways
- interaction handler slowed down by broad cache or REST work
- builders bypassed in favor of new raw response arrays for complex payloads

## Checklist before commit

- `Interaction::TYPES` still complete for supported types
- `InteractionCreate` still types, caches, and routes correctly
- resolved users/members/entitlements handled intentionally
- registered command execution and autocomplete still work through existing tree
- modal/component response behavior stays builder-friendly and protocol-correct
- boundaries with prefix command layer preserved
- docs/tests updated if public interaction behavior changed

## Bottom line

Interaction code in this repo is both protocol parsing and fast command routing. Keep it typed, cache-aware, and quick.

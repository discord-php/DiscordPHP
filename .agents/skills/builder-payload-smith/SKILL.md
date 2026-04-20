---
name: builder-payload-smith
description: >-
  Maintain Builder classes — outbound payload construction, validation,
  serialization, component handling, and fromPart symmetry. Use when
  changing Builders or outbound Discord API payloads.
---

# Skill: builder-payload-smith

Use this skill when work touches:

- `src/Discord/Builders/*`
- `src/Discord/Builders/Components/*`
- outbound payload construction paths in parts or interaction responses

This is outbound-shape skill. Load it when a change affects how callers build data to send to Discord.

## Goal

Keep builders as the repo's safe way to author complex outbound payloads:

- fluent and ergonomic for callers
- validated before hitting REST
- cleanly separated from in-memory parts
- serializable into Discord API payloads

## Read in this order

1. `src/Discord/Builders/Builder.php`
2. `src/Discord/Helpers/DynamicPropertyMutatorTrait.php`
3. `src/Discord/Builders/ComponentsTrait.php`
4. Representative builders:
   - `src/Discord/Builders/MessageBuilder.php`
   - `src/Discord/Builders/ChannelBuilder.php`
   - `src/Discord/Builders/CommandBuilder.php`
   - `src/Discord/Builders/ModalBuilder.php`
5. Component base types:
   - `src/Discord/Builders/Components/Component.php`
   - `src/Discord/Builders/Components/ComponentObject.php`
6. Matching part or repository family that consumes builder output

## Core contract

Builders are not parts. Builders:

- extend `Builder`
- often implement `JsonSerializable`
- keep mutable fluent state for outbound payloads
- validate limits and allowed values in setters or adders
- serialize to request-ready arrays
- sometimes provide `create($repository)` helpers for smoother public API

If a builder starts acting like cached domain object or repository, wrong layer.

## Base patterns to preserve

### `Builder::fromPart(Part $part)`

This is bridge from stored part data into editable payload state. Preserve it when adding new builder properties or part fields. Edit flows depend on this symmetry.

### `DynamicPropertyMutatorTrait`

Builder property access uses property-level mutators like:

- `setContent()`
- `getContent()`

Not part-style `getContentAttribute()`. Keep that distinction.

### `new()` factory

Most builders expose `new()` for userland ergonomics. Preserve pattern when adding new top-level builders.

### `create($repository)` helper

Newer builders often let callers hand the builder to a repository directly. Prefer keeping that path because it nudges users away from raw arrays.

## Validation rules

Validation should live as close as possible to setter/add method that introduces invalid state.

Good examples already in repo:

- message content length limits
- modal title and custom id length limits
- max embeds/components counts
- valid channel type enums
- valid video quality modes

Do not rely on far-away repository methods to catch simple builder invariants.

## Serialization rules

### `jsonSerialize()` is canonical outbound shape

This is where builder translates fluent internal state into Discord payload array. Keep it:

- explicit
- ordered enough to read
- faithful to API docs
- selective about optionals

### Omit unset optionals when possible

Many Discord APIs treat missing field differently from explicit null. Builders should usually omit properties that were never set unless API specifically wants null.

### Normalize nested builder or part inputs

If builder accepts nested objects:

- serialize nested builders
- convert parts to raw attributes or IDs where appropriate
- keep final payload JSON-friendly

## Boundaries with parts and repositories

### Builder vs part

- part = canonical resource model received from Discord or stored in memory
- builder = request payload authoring tool

Do not move outbound validation rules into parts if there is already a builder abstraction.

### Builder vs repository

- builder prepares payload
- repository performs REST call

Repositories should not become payload authoring zones full of hand-built nested arrays if builder exists.

## Component-specific rules

Component system exists in both builder and part worlds:

- builder components under `src/Discord/Builders/Components/*`
- inbound message component parts under `src/Discord/Parts/Channel/Message/*`

Keep these worlds aligned conceptually, but do not collapse them into one class family. Outbound builders and inbound parts solve different problems.

### `ComponentObject::TYPES`

If new outbound component subtype appears:

1. add constant
2. add map entry
3. add class
4. check matching inbound component family if relevant
5. update builders using components

### Usage contexts

Component usage constraints matter:

- message
- modal
- interaction contexts

Where repo already encodes allowed contexts, keep validation there instead of free-form component insertion.

## Builder selection guide

Add or extend a builder when:

- payload has multiple related fields
- Discord API has length/count/value limits
- nested structures are easy to get wrong by hand
- same shape is authored from several call sites

Raw array may be enough when:

- payload is tiny
- shape is stable and local
- repo does not already have builder convention for that family

But if users will author it directly or repeatedly, bias toward builder.

## Existing patterns worth copying

### Message builder

Best example for:

- content validation
- embeds/files/stickers/components combination
- fluent helpers
- repository `create()` bridge

### Channel builder

Best example for:

- type-specific optional fields
- enum validation
- payload fields that differ by channel subtype

### Command builder

Best example for:

- optional field omission
- payload shape depending on command type

### Modal builder

Best example for:

- strict component usage constraints
- response payload shape nested under `type` + `data`

## Smells

Stop if you see:

- raw arrays duplicated across many callers when builder exists
- validation postponed until after HTTP call
- builder storing cache/domain state
- repository assembling complicated payload shape that should live in builder
- mismatch between builder and part edit flow because `fromPart()` path no longer reflects real fields
- new component type added only on one side of builder/part system

## Builder change checklist

- new property has setter/getter if public
- validation lives near write path
- `jsonSerialize()` includes it only when appropriate
- `fromPart()` behavior still makes sense
- any `create($repository)` helper remains correct
- component/type maps updated if subtype introduced
- tests cover invalid and valid boundary cases
- docs/examples updated if preferred public usage changed

## Bottom line

Builders in this repo exist to keep outbound payload rules from leaking everywhere. If a caller has to memorize Discord payload trivia instead of relying on builder methods, builder design is unfinished.

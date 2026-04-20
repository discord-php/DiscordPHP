---
name: type-map-keeper
description: >-
  Maintain TYPES maps and subtype dispatch — Channel::TYPES,
  Interaction::TYPES, Component types, and related constants. Use when
  adding subtypes or changing polymorphic dispatch.
---

# Skill: type-map-keeper

Use this skill when work touches:

- any `TYPES` constant array on a part or builder
- `TYPE_*` constants on channel, interaction, component, or embed families
- code that dispatches by `type`, `component_type`, or similar runtime discriminator
- `attributeTypedCollectionHelper()` or `attributePartHelper()` calls that consume a `TYPES` map
- `ChannelBuilder::TYPES` alias or equivalent builder-side mirrors

This is subtype-dispatch skill. Load it when a change adds, removes, or reorganizes how the codebase resolves a polymorphic Discord payload into a concrete PHP class.

## Goal

Keep type maps as the single source of truth for polymorphic dispatch:

- one constant array decides which class represents each discriminator value
- all materialization sites consume that array instead of branching ad hoc
- builder-side mirrors stay aligned with inbound part maps
- fallback at index `0` handles unknown future values safely
- public `TYPE_*` constants remain stable vocabulary for userland

## Read in this order

1. `src/Discord/Parts/Channel/Channel.php` — `Channel::TYPES` and `TYPE_*` constants, deprecated aliases
2. `src/Discord/Parts/Interactions/Interaction.php` — `Interaction::TYPES` and `TYPE_*` constants
3. `src/Discord/Parts/Embed/Embed.php` — `Embed::TYPES` with string-keyed discriminators
4. `src/Discord/Parts/Channel/Message/Component.php` — inbound `Component::TYPES` map
5. `src/Discord/Builders/Components/ComponentObject.php` — outbound `ComponentObject::TYPES` and `TYPE_*` constants
6. `src/Discord/Parts/PartTrait.php` — `attributeTypedCollectionHelper()` at line ~530, `createOf()` at line ~429
7. `src/Discord/Builders/ChannelBuilder.php` — `ChannelBuilder::TYPES = Channel::TYPES` alias
8. Representative event handlers that dispatch through maps:
   - `src/Discord/WebSockets/Events/ChannelCreate.php`
   - `src/Discord/WebSockets/Events/InteractionCreate.php`
   - `src/Discord/WebSockets/Events/ThreadListSync.php`

## Core contract

A `TYPES` constant is a `public const` array on the root class of a polymorphic family. Keys are discriminator values (integers or strings) sent by Discord in the `type` field. Values are fully-qualified class names of concrete subtypes. Index `0` (or equivalent) holds the root class itself as a safe fallback for unrecognized discriminator values.

All code that needs to turn a raw discriminator into a class **must** look up the map instead of writing its own switch/match/if chain. This keeps subtype knowledge centralized: add one map entry and every dispatch site picks it up automatically.

## How type maps work

### Map shape

```php
public const TYPES = [
    0 => self::class,                       // fallback
    self::TYPE_PING => Ping::class,         // known subtypes
    self::TYPE_APPLICATION_COMMAND => ApplicationCommand::class,
    // ...
];
```

The discriminator field varies by family: `type` for channels, interactions, and embeds; `type` or `component_type` for components. The `attributeTypedCollectionHelper()` in `PartTrait` handles both via `$part->type ?? $part->component_type ?? 0`.

### Dispatch expression

At every materialization site, the pattern is:

```php
$class::TYPES[$data->type ?? 0]
```

This resolves to either a known concrete class or the fallback root class. Event handlers may add a secondary fallback with `?? Channel::class` for families like channels that lack an explicit index `0` entry.

## Known type map families

| Root class | Discriminator | Fallback | String keys | Builder mirror |
| --- | --- | --- | --- | --- |
| `Channel` | `type` (int) | none — events use `?? Channel::class` | no | `ChannelBuilder::TYPES` |
| `Interaction` | `type` (int) | index `0 => Interaction::class` | no | — |
| `Embed` | `type` (string) | index `0 => Embed::class` | yes (`'rich'`, `'image'`, …) | — |
| `Component` (inbound) | `type` / `component_type` (int) | index `0` via helper | no | — |
| `ComponentObject` (outbound) | `type` (int) | index `0` via helper | no | is the builder side |

`Component::TYPES` and `ComponentObject::TYPES` are independent arrays that happen to share the same key space and constant names. They map to different class hierarchies — inbound parts vs outbound builders.

## Materialization sites

Type maps are consumed in these code paths:

### 1. Gateway event handlers

Event classes call `$this->factory->part(TYPES[$data->type] ?? Fallback::class, ...)` to hydrate the correct subtype from raw gateway payloads.

- `ChannelCreate`, `ChannelUpdate`, `ChannelDelete` use `ChannelBuilder::TYPES[$data->type] ?? Channel::class`
- `ThreadCreate`, `ThreadUpdate`, `ThreadDelete`, `ThreadListSync` use the same channel map
- `InteractionCreate` uses `Interaction::TYPES[$data->type ?? 0]`

### 2. `attributeTypedCollectionHelper()` in PartTrait

Called by mutators like `Message::getEmbedsAttribute()` and `Message::getComponentsAttribute()` to build typed collections from raw payload arrays:

```php
$part = $this->createOf($class::TYPES[$part->type ?? $part->component_type ?? 0], $part);
```

### 3. `attributePartHelper()` for single-object dispatch

Used by `Section::getAccessoryAttribute()`, `Label::getComponentAttribute()`, and `Component::getComponentAttribute()` to resolve a single nested object:

```php
return $this->attributePartHelper('accessory', Component::TYPES[$this->attributes['accessory']->type ?? 0]);
```

### 4. Resolved data hydration

`Resolved::getChannelsAttribute()` uses `ChannelBuilder::TYPES[$channel->type] ?? Channel::class` to hydrate resolved channels from interaction payloads.

### 5. Builder alias forwarding

`ChannelBuilder::TYPES = Channel::TYPES` makes the builder a transparent proxy. Event handlers reference `ChannelBuilder::TYPES` so the builder layer can theoretically override mapping without changing the part, though today both are identical.

## Adding a new subtype

Follow this sequence:

1. **Add `TYPE_*` constant** on the root class, matching the Discord API integer/string value.
2. **Create the concrete class** extending the root part (or sibling base). Keep it in the same namespace family.
3. **Add the map entry** in the root class `TYPES` array, keyed by the new constant.
4. **Update event handlers** — any gateway event that hydrates this family will automatically pick up the new entry if it reads `TYPES`. Verify the fallback expression still works.
5. **Update builder-side mirror** if one exists. For channels, `ChannelBuilder::TYPES` is an alias so it picks up changes automatically. For components, `ComponentObject::TYPES` must be updated independently.
6. **Update typed collection helpers** — if the new subtype appears in a collection context (embeds, components), confirm `attributeTypedCollectionHelper()` resolves it.
7. **Add docblocks and `@property` annotations** on parent parts if the subtype surfaces through typed collections.
8. **Update `$fillable`** and any subtype-specific mutators, repositories, or permission checks.
9. **Preserve deprecated aliases** if the new constant replaces an older name.

## Builder-side mirrors

The component family has independent inbound and outbound type maps:

- **Inbound**: `Component::TYPES` under `src/Discord/Parts/Channel/Message/Component.php` maps to part classes like `ActionRow`, `Button`, `StringSelect` (the inbound representations).
- **Outbound**: `ComponentObject::TYPES` under `src/Discord/Builders/Components/ComponentObject.php` maps to builder classes like `ActionRow`, `Button`, `StringSelect` (the builder representations).

Both maps share the same `TYPE_*` integer constants defined on `ComponentObject`. When Discord adds a new component type, both maps must be extended. Missing one side means either inbound deserialization or outbound construction silently falls back to the wrong class.

For channels, `ChannelBuilder::TYPES = Channel::TYPES` is a simple alias, so there is no independent map to forget. But if channel builder logic ever diverges, the alias approach should be revisited.

## Fallback behavior

Index `0` is the conventional fallback key. When Discord introduces a type value the library does not yet support:

- `Interaction::TYPES[0]` resolves to `Interaction::class` — a safe generic interaction
- `Embed::TYPES[0]` resolves to `Embed::class` — a safe generic embed
- `Channel::TYPES` has no index `0`, so event handlers append `?? Channel::class` as explicit fallback

The `attributeTypedCollectionHelper()` defaults to `$part->type ?? $part->component_type ?? 0`, which falls through to index `0` when neither field is present.

New families should include an explicit index `0` fallback pointing to the root class. Relying on `?? RootClass::class` at every call site is fragile and easy to forget.

## Constants as public vocabulary

`TYPE_*` constants serve as stable API for userland code:

```php
if ($channel->type === Channel::TYPE_GUILD_VOICE) { ... }
```

### Naming convention

- Prefix: `TYPE_` (always)
- Body: uppercase snake_case matching Discord's enum name (e.g., `TYPE_GUILD_TEXT`, `TYPE_APPLICATION_COMMAND`)
- Constants are defined on the root class, not on subtypes

### Deprecated alias preservation

When Discord renames a concept, keep the old constant as a deprecated alias:

```php
/** @deprecated 10.0.0 Use `Channel::TYPE_GUILD_ANNOUNCEMENT` */
public const TYPE_NEWS = self::TYPE_GUILD_ANNOUNCEMENT;
```

Channel.php carries over a dozen such aliases. Do not remove them without a major version bump. Add `@deprecated` with the version and migration target.

## Smells

Stop if you see:

- new subtype class created but no entry added to the family `TYPES` map
- hardcoded `if ($type === 5)` branch when a map lookup would work
- builder-side component map updated but inbound `Component::TYPES` left stale, or vice versa
- event handler using its own local type-to-class mapping instead of the canonical `TYPES` constant
- `attributeTypedCollectionHelper()` called with a class whose `TYPES` constant does not exist
- missing fallback causing crash on unknown discriminator value from a newer Discord API version
- deprecated constant alias removed without major version bump
- `TYPE_*` constant defined on a subtype instead of the root class

## Checklist before commit

- [ ] `TYPE_*` constant added or updated on the root class
- [ ] `TYPES` map entry added with constant key → class value
- [ ] Concrete subtype class created in same namespace family
- [ ] Fallback index `0` present or explicit `?? RootClass::class` at all dispatch sites
- [ ] Builder-side mirror updated if family has one (`ComponentObject::TYPES`, `ChannelBuilder::TYPES`)
- [ ] Event handlers verified — they should pick up new type automatically via map lookup
- [ ] `attributeTypedCollectionHelper()` and `attributePartHelper()` callers verified
- [ ] Deprecated aliases preserved with `@deprecated` docblock if renaming
- [ ] Docblocks on root class updated for new constant
- [ ] Tests cover the new subtype's basic instantiation and type resolution

## Bottom line

Type maps centralize subtype dispatch so that adding a new Discord payload variant is a map entry, not a codebase-wide scavenger hunt through branching logic.

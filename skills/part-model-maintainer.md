# Skill: part-model-maintainer

Use this skill when work touches `src/Discord/Parts/**/*`.

This is not syntax guard. This is domain-model guard. Load it when changing what a Discord resource **is**, how it is hydrated, how it exposes related objects, or how it saves itself.

## Goal

Keep `Part` classes as the canonical in-memory representation of Discord resources:

- raw API-shaped data lives in attributes
- typed access lives through mutators and helpers
- persistence delegates to repositories
- permission and high-level domain rules stay close to the part
- public magic surface stays documented

## Read in this order

1. `src/Discord/Parts/Part.php`
2. `src/Discord/Parts/PartTrait.php`
3. Representative concrete parts for the family you are touching:
   - `src/Discord/Parts/Guild/Guild.php`
   - `src/Discord/Parts/Channel/Channel.php`
   - `src/Discord/Parts/Channel/Message.php`
   - `src/Discord/Parts/User/User.php`
   - `src/Discord/Parts/User/Member.php`
   - `src/Discord/Parts/Thread/Thread.php`
   - `src/Discord/Parts/Interactions/Interaction.php`
4. The owning repository for the part
5. Gateway events that hydrate or update the part

Do not start from a random leaf class alone. `PartTrait` defines most real behavior.

## Core contract

Every real part sits on same base contract:

- extends `Discord\Parts\Part`
- inherits `PartTrait`
- constructed with `(Discord $discord, array $attributes = [], bool $created = false)`
- stores raw Discord payload fields in `$attributes`
- only mass-assigns keys in `$fillable`
- exposes related repositories from `$repositories`
- resolves computed or typed values through `get{Studly}Attribute()`
- normalizes writes through `set{Studly}Attribute()`
- persists through `getRepository()` + `save()`

If change breaks one of those assumptions, stop and re-check layer ownership.

## Meaning of common properties

### `$fillable`

Whitelist for `fill()` and dynamic writes. If field is missing here:

- gateway hydration ignores it
- REST fetch hydration ignores it
- direct `$part->field = ...` writes do not persist to `$attributes`
- magic property docblocks become misleading

When adding new API field, first ask: should this be part of canonical stored state? If yes, add it to `$fillable`.

### `$attributes`

Raw storage. Usually snake_case and Discord-shaped. Avoid putting derived state here unless the API itself uses that field.

### `$repositories`

Map of magic property name to repository class. This is how parts expose child collections like:

- `$guild->channels`
- `$channel->messages`
- `$message->reactions`

If child data should behave like a repository, wire it here. Do not fake repo behavior with arrays.

### `$created`

Tracks whether part already exists on Discord side.

- `false` means local draft or partial unsaved object
- `true` means known remote object

Repository `save()` uses this to choose POST vs PATCH. Nested `createOf()` links child `created` state back to parent. Preserve that when materializing nested parts.

## Core methods and what they mean

### `fill(array $attributes)`

Hydrates only fillable keys. It is not "dump every payload field in object". If new data is not appearing after fetch/event handling, check `$fillable` first.

### `getAttribute()` / `__get()`

Resolution order matters:

1. repository from `$repositories`
2. getter mutator
3. raw attribute
4. `null`

This is why magic properties can expose both repositories and computed values. Preserve this behavior when adding convenience fields.

### `setAttribute()` / `__set()`

Setter mutator first, then raw write only if key is fillable. This protects parts from accidental shape drift. Do not bypass it by writing stray protected properties for true resource state.

### `getCreatableAttributes()` / `getUpdatableAttributes()`

These define what the repository sends to Discord. They are not debug dumps. They should express API intent:

- required vs optional
- create vs update differences
- omit optional values that were never set when API cares about missing vs null

Prefer `makeOptionalAttributes()` to avoid serializing absent optional fields.

### `getRepository()`

Returns the owning repository for this part instance. This is frequently context-sensitive:

- `Guild` returns top-level `$discord->guilds`
- `Channel` chooses guild channels vs private channels
- `Message` chooses channel messages vs webhook messages
- `Member` depends on `guild_id`
- `Thread` depends on parent channel

When repository ownership depends on parent IDs, also inspect `getRepositoryAttributes()`.

### `save(?string $reason = null)`

Part-level `save()` is where high-level semantic guards live:

- permission checks
- ownership routing
- special-case endpoints for current user/current member/group DM/webhook cases

Rule: if logic needs knowledge of what operation **means**, it likely belongs here. If logic only knows how to execute REST+cache, it belongs in repository.

### `fetch()`

Only override when part can be refreshed independently from Discord and that contract is meaningful. If part is not fetchable, default runtime exception is fine.

## Helper methods to prefer

### `attributeCarbonHelper($key)`

Use for timestamp-ish fields. Keeps repeated `Carbon::parse` logic out of concrete parts.

### `attributePartHelper($key, $class, $extraData = [])`

Use when field is a nested Discord object and you want lazy typed hydration with caching in `$attributes`.

Good for:

- nested `User`
- nested `Guild`
- nested `Message`
- nested message metadata objects

### `attributeCollectionHelper($key, $class, ?string $discrim = 'id', ?array $extraData = [])`

Use for homogeneous nested collections of one concrete part type.

### `attributeTypedCollectionHelper($class, $key)`

Use when payload element type decides subclass at runtime. This is important for:

- message components
- other subtype families with `TYPES` maps

### `createOf(string $class, array|object $data)`

Prefer this over raw factory calls for nested child parts because it preserves `created` linkage.

## Part design patterns already used in repo

### Raw ID plus resolved relation

Common pair:

- raw: `guild_id`, `channel_id`, `owner_id`
- resolved: `guild`, `channel`, `owner`

Keep both if the API provides IDs and the library benefits from relation convenience.

### Constants as public vocabulary

Large resource parts publish Discord enum values and flags as class constants. Keep aliases when the project already supports deprecated constant names for compatibility.

### Traits for shared semantics

If behavior applies horizontally across siblings, prefer trait:

- `ChannelTrait`
- `GuildTrait`

Do not introduce a new intermediate abstract class unless there is truly no cleaner trait shape.

### PHPDoc as magic surface contract

Update class docblock whenever you add:

- fillable raw field
- computed read-only property
- repository property
- new typed collection

If code and docblock drift, IDE help and generated reference docs drift too.

## Save and routing playbook

When making a part persistable or changing save behavior:

1. Define or update `getCreatableAttributes()`
2. Define or update `getUpdatableAttributes()`
3. Define or update `getRepository()`
4. Define or update `save()`
5. Define or update `getRepositoryAttributes()`
6. Inspect owning repository endpoint vars
7. Inspect gateway events that hydrate/update the part

### Examples worth copying

- `Channel::save()` handles guild permission checks and group-DM special case before repository delegation
- `Message::save()` handles send/manage permissions and webhook message routing
- `Member::save()` handles current-member special route instead of generic repository save
- `Thread::save()` blocks unsupported creation path and redirects callers to `Channel::startThread()`

## Nested data playbook

When new Discord payload includes nested object:

1. Ask if repo already has a `Part` type for it
2. If yes, add field to `$fillable`
3. Add getter mutator using helper
4. Add docblock type
5. If nested collection, choose homogeneous vs typed collection helper
6. If nested item needs parent IDs, pass `extraData`

Do not leave meaningful nested objects as raw arrays if equivalent typed part exists.

## Repository-binding playbook

When child repository routes depend on parent context:

1. expose repository in `$repositories`
2. make sure parent part has required IDs in `$attributes`
3. override `getRepositoryAttributes()` when raw `$attributes` is not enough or not in right shape
4. inspect repository constructor for `$vars` assumptions

Examples:

- channels need `guild_id` and `channel_id`
- messages need `channel_id`, maybe `guild_id`, maybe webhook context
- members need `guild_id`

## Smells

Stop if you see:

- raw arrays where typed nested parts already exist
- new field added to docblock but not to `$fillable`
- `save()` building raw endpoints even though repository exists
- permission check added only in repository while part clearly knows semantic action
- part state stored in new ad hoc property instead of `$attributes`
- child repository added without `getRepositoryAttributes()` support
- subtype family added without updating a `TYPES` map or typed collection helper path

## Checklist before commit

- `$fillable` matches intended canonical fields
- getter/setter mutators added where typing or normalization needed
- `$repositories` updated if new child collection exposed
- `getCreatableAttributes()` / `getUpdatableAttributes()` express API semantics
- `getRepository()` and `getRepositoryAttributes()` route correctly
- `save()` enforces semantic permission or special-case behavior when needed
- class docblock reflects public magic surface
- related repository and gateway event code still coherent
- tests/docs updated if public behavior changed

## Bottom line

Part classes in this repo are not dumb DTOs and not service objects. They are typed, lazily-resolved domain resources with controlled hydration and repository-backed persistence. Keep them centered on that job.

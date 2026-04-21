# DiscordPHP Agent Guide

This file is the repo operating manual for AI agents. Use it together with `SKILLS.md`.

- `SKILLS.md` tells you **which specialist mindset to load** for a task.
- `AGENTS.md` tells you **how to work inside this repository without breaking its design**.

If a change crosses layers, load multiple skills and use the playbooks in this file to keep boundaries clean.

## Start here

Before changing anything:

1. Identify the layer you are touching.
2. Read the base abstraction for that layer before copying a concrete class.
3. Read one representative concrete implementation from the same family.
4. Trace how that layer connects to adjacent layers.
5. Make the change in the narrowest layer that can own it.
6. Update companion surfaces that define the same contract.

In this repo, companion surfaces usually matter as much as the line you edit.

## Non-negotiable truths

1. **CLI-only runtime.** DiscordPHP is a long-running process built on ReactPHP. Do not design around web requests, controllers, middleware stacks, or per-request state.
2. **Async first.** Production I/O is Promise-based. Blocking helpers belong in tests only.
3. **Parts are canonical domain objects.** They model Discord resources and expose typed magic properties.
4. **Repositories are persistence and cache boundaries.** They are not generic service classes.
5. **Gateway handlers keep caches coherent.** They do more than relay notifications.
6. **Builders own outbound payload rules.** If a payload has meaningful shape or validation, it usually deserves a builder.
7. **Docblocks are runtime-adjacent documentation.** They are not optional decoration.
8. **Traits are preferred over deep inheritance or broad interface hierarchies for shared behavior.**
9. **Type maps are central dispatch points.** If a Discord payload is polymorphic, there is usually one place that decides the concrete subtype.

## Public API policy

DiscordPHP is a **library, not a framework.** User code calls DiscordPHP; DiscordPHP does not call user code beyond the event listeners the user explicitly registers. This posture is deliberate and is the gate against feature creep. If a proposed addition would invert that control flow, it does not belong here — it belongs in a sibling framework package layered on top.

### The dividing rule

A capability belongs **in DiscordPHP (this library)** if **all** of the following are true:

- It lives in exactly one existing layer (Part, Repository, Builder, Event, Voice, or runtime).
- It wraps a primitive nicer without choosing an app shape.
- It holds no state the caller would otherwise control.
- It does not own a lifecycle beyond `__construct` / `run()` / `close()`.
- It does not assume a config file format, DI container, or plugin model.
- Its default behavior is correct for the overwhelming majority of callers, and wrong defaults are still overridable.

A capability belongs **outside this library** if **any** of the following are true:

- It imposes app structure (routing, controllers, plugin registry, modules).
- It owns config resolution beyond `.env` + constructor options.
- It holds cross-event state on the user's behalf (pagination cursors, wizards, cooldowns, session tracking).
- It couples subsystems users should be free to swap (e.g. command routing + persistence + permission system together).
- It wants a DI container, service providers, or its own CLI.

Ambiguous cases default to **outside**. It is far harder to remove a public abstraction than to keep it out.

### Rules (R1–R10)

These are design heuristics, not commandments. They exist to serve the dividing rule above.

- **R1. One obvious entry point per task, progressive disclosure.** `Discord::fromEnv()` is the happy path; the full constructor is still there. Do not ship three ways to do the same thing.
- **R2. Listeners over lifecycle ceremony for the common path.** Thin aliases like `onReady()`, `onMessage()` keep first-bot code short. Reserve `on(Event::X, ...)` for cases where a constant is actually clearer.
- **R3. Builders are the single outbound authoring path.** If a payload has meaningful shape or validation, improve the builder. Do not add convenience methods that bypass it.
- **R4. No raw payloads in userland.** Handlers, events, and repository methods must return typed Parts or typed collections. Array-diving in userland is a library failure.
- **R5. Fail loudly, fail actionably.** Every thrown exception tells the user what to do next (env var, option key, portal toggle). See `Discord::fromEnv()` for the reference tone.
- **R6. No hidden singletons.** `Discord` is an instance. Never add global registry lookups — they break multi-bot processes, tests, and DI.
- **R7. Layered, not monolithic.** Core `Discord` works without `DiscordCommandClient`. Voice works without slash commands. Users opt in.
- **R8. Async visible, taught once.** Promises are part of the contract. Document the one pattern (`->then()` / `->done()`) in the quickstart and reuse it everywhere.
- **R9. Every public addition needs docblock + example + test.** If it is not all three, it is not part of the public surface.
- **R10. Conventions over config, with a real escape hatch.** Good defaults for intents, cache, logger — but every default is overridable via the same options array. No "rebuild to change X" moments.

### Explicit non-goals (belong in a framework package, not here)

Named so that future proposals get a clear, reviewable "out of scope" response. These are not forbidden ideas — they are simply not this library's job:

- **Slash command router.** Attribute- or class-based dispatch to handler methods. Imposes an app shape.
- **CommandRegistrar / diff-and-apply registration.** Holds desired-state across invocations and owns a lifecycle.
- **Interaction router.** Pattern-matching `custom_id` → handler; inherently a routing concern.
- **Menu / pagination / wizard state helpers.** Hold cross-event state on the user's behalf.
- **Permission / cooldown middleware.** Policy over primitives; middleware implies a pipeline the library does not own.

A starter document for such a framework project lives at `todo.txt` at the repo root as a temporary placeholder; it is intended to be moved into its own repository.

### Decision record hook for reviewers

If a proposed PR fails the dividing rule, the reviewer should either:

1. Ask the author to rewrite it as a single-layer primitive that does pass the rule, **or**
2. Close the PR with a note suggesting the idea belongs in an external framework package (link `todo.txt` while it still exists, or the future framework repo).

Do not merge "just this one" exceptions. The rule is only useful if it is consistently applied.

## Architecture map

| Layer | Owns | Primary files | What to preserve |
| --- | --- | --- | --- |
| Runtime | process lifecycle, options, loop, gateway, HTTP, root repos | `src/Discord/Discord.php` | `__construct()` wires dependencies and connects; `run()` only starts loop |
| Factory | part/repository instantiation | `src/Discord/Factory/Factory.php` | callers should not construct repo families ad hoc |
| Parts | domain objects, mutators, typed nested data, high-level operations | `src/Discord/Parts/Part.php`, `src/Discord/Parts/PartTrait.php`, `src/Discord/Parts/**/*` | `$fillable`, mutators, PHPDoc, `save()` semantics, `created` lifecycle |
| Repositories | typed collections, cache, REST endpoints, CRUD | `src/Discord/Repository/AbstractRepository.php`, `src/Discord/Repository/**/*` | `$class`, `$endpoints`, `$vars`, cache writes, Promise-based API |
| Builders | outbound payload construction and validation | `src/Discord/Builders/**/*` | fluent setters, validation, `jsonSerialize()`, `fromPart()` symmetry |
| Gateway events | payload hydration, cache mutation, emitted return shapes | `src/Discord/WebSockets/Handlers.php`, `src/Discord/WebSockets/Event.php`, `src/Discord/WebSockets/Events/*` | typed part creation, related cache updates, event contract shape |
| Optional command layer | message-prefix command UX | `src/Discord/DiscordCommandClient.php`, `src/Discord/CommandClient/Command.php` | keep it layered on top of core client, not inside it |
| Tests and docs | behavioral contract | `tests/*`, `guide/*`, `README.md`, `docs/*` | async testing patterns, public guidance, docblock reference surface |

## Repo worldview

### Runtime to domain flow

`Discord` owns bootstrapping. Gateway dispatch goes through `Handlers` into a dedicated event class. Event classes translate payloads into typed parts and update repositories. Repositories provide cache and REST persistence. Parts expose that state through magic properties and typed helpers. Builders assemble outbound payloads for operations that would otherwise become fragile arrays.

### Why the split matters

- If you put transport logic in parts, parts stop being stable domain objects.
- If you put domain validation in repositories, repository APIs stop being predictable.
- If you skip builders, payload rules spread across unrelated methods.
- If gateway handlers do not update caches correctly, every downstream relation becomes stale.

## Common class patterns

### Parts

Expect these elements on real resource models:

- large class-level `@property` and `@property-read` docblocks
- `protected $fillable = [...]`
- optional `protected $repositories = [...]`
- constants mirroring Discord enums or flags
- `getXAttribute()` and `setXAttribute()` mutators
- overrides for `getCreatableAttributes()`, `getUpdatableAttributes()`, `getRepository()`, `save()`, `fetch()`, or `getRepositoryAttributes()` when the part is persistable

Semantic rules:

- raw attribute names stay snake_case to match Discord payloads
- convenience relations (`guild`, `channel`, `owner`, `member`) are usually computed, not directly stored
- nested typed data should become a `Part`, typed collection, or `Carbon` value through helper methods
- permission checks for high-level mutations belong on the part before repository delegation
- `created` tells you whether the object already exists remotely

### Repositories

Expect these elements:

- `extends AbstractRepository`
- `protected $class = SomePart::class`
- `protected $endpoints = [...]`
- optional constructor normalization for route vars
- occasional domain-specific convenience methods

Semantic rules:

- repositories are typed collections plus REST/cache wrappers
- `create()` builds a local part; `save()` persists it
- `$vars` carries parent route context like `guild_id` or `channel_id`
- cache writes must stay aligned with REST writes and gateway updates
- special methods should still return typed parts or repositories, not loose payloads

### Builders

Expect these elements:

- `extends Builder`
- `implements JsonSerializable`
- fluent `setX()` / `getX()` methods
- eager validation in setters or adders
- `new()` factory method on most builders
- `create($repository)` helper on newer builders

Semantic rules:

- builders are not parts and should not own persistence or cache logic
- validation belongs here when it describes outgoing payload shape
- `jsonSerialize()` should omit unset optionals when Discord distinguishes missing from explicit null
- `fromPart()` should make edit flows symmetrical

### Gateway events

Expect these elements:

- one class per event type under `src/Discord/WebSockets/Events`
- matching constant in `src/Discord/WebSockets/Event.php`
- matching registration in `src/Discord/WebSockets/Handlers.php`
- `handle($data)` method returning typed semantic values

Semantic rules:

- event handlers should hydrate the right subtype on first read
- event handlers are responsible for repository/cache coherence
- update events often return both new and old state
- delete events often return cached removed state, not only the raw payload id
- related user/member caches usually need updating too

## Cross-layer rules

### 1. Parts delegate persistence; repositories own REST

If a part can be saved:

- the part decides **whether** the action is allowed and which repository owns it
- the repository decides **how** to call Discord and update cache

Smell: a part method manually building endpoints and PATCH payloads when a repository already exists for that family.

### 2. Parts own semantics; builders own payload ergonomics

If userland needs to construct a non-trivial outbound payload:

- add or extend a builder
- keep raw-array construction as an implementation detail only when a builder would be needless overhead

Smell: multiple methods assembling the same nested array by hand.

### 3. Gateway events own reactive cache updates

If Discord can tell us something through gateway dispatch:

- prefer updating cache from the event instead of forcing later REST refetches
- keep parent/child repository relationships coherent at the same time

Smell: event handler creates a part but does not update the repository that should own it.

### 4. Traits carry horizontal behavior

The codebase prefers traits for shared capabilities across sibling models.

Examples:

- `PartTrait` for universal part mechanics
- `ChannelTrait` for channel/thread behavior
- `GuildTrait` for guild asset and feature helpers
- `DynamicPropertyMutatorTrait` for builder/property mutators

Smell: new abstract intermediate base class that only exists to share a few methods between peers that already have a common root.

### 5. Type maps beat repeated branching

When a Discord discriminator chooses a subtype:

- extend the relevant `TYPES` map
- update all materialization sites that depend on that family

Smell: one event handler special-cases a new subtype but the central type map still does not know it.

## Common companion surfaces

If you touch one of these, inspect the companions too:

| Touching | Also inspect |
| --- | --- |
| `src/Discord/Parts/Part.php` or `PartTrait.php` | representative parts, `PartInterface`, generated docblocks |
| a concrete `Part` | owning repository, related trait, event handlers, tests, docs |
| a nested repository relationship | parent part `$repositories`, `getRepositoryAttributes()`, repository constructor vars |
| `src/Discord/Repository/AbstractRepository*` | representative repos, cache wrapper behavior, part save/fetch overrides |
| a gateway event class | `Event.php`, `Handlers.php`, related part/repository, tests |
| a builder | matching part, callers, tests, docs/examples |
| `Channel::TYPES`, `Interaction::TYPES`, component/ embed maps | event handlers, typed collection helpers, builder mirrors |
| `DiscordCommandClient` | `CommandClient/Command.php`, examples/docs |
| public magic properties | class PHPDoc, guide docs if user-facing behavior changed |

## Change playbooks

### Playbook: editing a Part

1. Update `$fillable`.
2. Add or adjust mutators for typed nested data, computed properties, or normalization.
3. Update class docblocks.
4. Update `$repositories` if a new child repository is exposed.
5. Update `getRepositoryAttributes()` if child route vars changed.
6. Update `getCreatableAttributes()` / `getUpdatableAttributes()` if persistence shape changed.
7. Update `getRepository()` or `save()` if ownership or permission rules changed.
8. Check gateway events and repositories that hydrate or cache the part.
9. Add or update tests.

### Playbook: editing a Repository

1. Confirm `$class`, `$discrim`, and `$endpoints` still describe the family correctly.
2. Confirm parent route vars are complete and in the correct shape.
3. Keep REST writes and cache writes in sync.
4. Return typed values.
5. Check owning parts for `getRepository()` and `getRepositoryAttributes()` assumptions.
6. Update tests and docs if public behavior changed.

### Playbook: editing a Builder

1. Put payload validation in setters/adders.
2. Keep fluent chaining style.
3. Keep `jsonSerialize()` aligned with Discord docs and existing payload semantics.
4. Keep `fromPart()` edit symmetry in mind.
5. Update tests for validation limits and payload shape.
6. Update docs/examples if the preferred public construction path changed.

### Playbook: editing a gateway event

1. Add or update the event constant in `Event.php` if needed.
2. Add or update the handler registration in `Handlers.php`.
3. Hydrate the correct subtype.
4. Update every affected repository and relation cache.
5. Preserve event return shape expected by userland listeners.
6. Cache related users/members if the payload supplies them.
7. Re-check any intent-gated or partial-data behavior.

### Playbook: editing interactions or commands

1. Keep application-command and prefix-command layers separate.
2. Keep interaction typing and resolved-data hydration intact.
3. Keep builders as the outbound authoring path for commands, components, and modals.
4. Avoid slow interaction-time work that can delay a response.
5. Update both inbound event handling and outbound builder/docs surfaces if public behavior shifts.

## Design tripwires

If you see one of these, slow down:

- a new raw nested array where a typed part already exists
- a repository method returning raw decoded payload instead of a part
- a part saving itself with hand-built endpoints even though an owning repository exists
- a new subtype without a `TYPES` map entry
- a magic property added in code but not in docblocks
- a gateway handler that updates one cache but leaves related repositories stale
- a synchronous wait or loop-stop trick outside tests
- a new abstraction layer that duplicates what parts, repositories, events, or builders already do
- web-framework terminology creeping into core runtime code

## Preferred reference files

When you need an example worth imitating, start here:

- Runtime orchestration: `src/Discord/Discord.php`
- Base part mechanics: `src/Discord/Parts/Part.php`, `src/Discord/Parts/PartTrait.php`
- Rich part model: `src/Discord/Parts/Guild/Guild.php`
- Channel/resource semantics: `src/Discord/Parts/Channel/Channel.php`
- Message semantics and repository binding: `src/Discord/Parts/Channel/Message.php`
- Repository baseline: `src/Discord/Repository/AbstractRepository.php`, `src/Discord/Repository/AbstractRepositoryTrait.php`
- Simple repo specialization: `src/Discord/Repository/Channel/MessageRepository.php`
- Rich repo specialization: `src/Discord/Repository/GuildRepository.php`
- Outbound builder style: `src/Discord/Builders/MessageBuilder.php`
- Interaction typing: `src/Discord/Parts/Interactions/Interaction.php`
- Gateway cache mutation: `src/Discord/WebSockets/Events/MessageCreate.php`, `src/Discord/WebSockets/Events/GuildCreate.php`
- Optional command layer: `src/Discord/DiscordCommandClient.php`, `src/Discord/CommandClient/Command.php`

## Testing and docs workflow

### Tests

- Write all tests in **Pest 4** using `it()` or `test()` functions — no class-based test files.
- Unit tests (no Discord token needed) go in the `unit` testsuite. Integration tests go in the `integration` testsuite; list new files in `phpunit.xml`.
- Use `uses(DiscordTestCase::class)->in(...)` in `tests/Pest.php` only when a test file needs `$this->channel()` or other integration helpers.
- Use `wait()` from `tests/functions.php` to bridge ReactPHP promises into test assertions.
- Keep semantic tests focused on behavior, not on incidental implementation details.

### Docs

- Public magic properties, repositories, and helpers should be reflected in PHPDoc.
- Long-form guides live in `guide/`.
- Gatsby docs live in `docs/`.
- Keep docs in sync when preferred usage or public contracts change.

## Useful commands

| Purpose | Command |
| --- | --- |
| unit test suite (no Discord token needed) | `composer unit` |
| integration test suite (requires `.env`) | `composer integration` |
| static analysis | `composer run-script mago-lint` |
| formatter contributors run | `composer run-script cs` |
| non-mutating Pint check | `./vendor/bin/pint --test --config ./pint.json ./src` |
| docs build | `cd docs && yarn install && yarn build` |

Integration tests expect `.env` values for `DISCORD_TOKEN`, `TEST_CHANNEL`, and `TEST_CHANNEL_NAME`.

Tests are written in **Pest 4** (`it()` / `test()` functions). Use `uses(SomeClass::class)->in(...)` in `tests/Pest.php` to bind a base class to specific test files.

## Final rule

When unsure where code belongs, choose the layer that already owns the same kind of knowledge elsewhere in the repo. Matching the existing ownership model matters more than shaving a few lines off one class.

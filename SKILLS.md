# DiscordPHP Maintenance Skills

This file defines reusable maintenance skills for AI agents working in this repository. These skills are about preserving the repo's semantic design, not merely matching formatting. Load the skills that match the change. Combine them when a task crosses layers.

## Shared doctrine

Before loading any specific skill, anchor on these rules:

1. **This is a CLI-only, long-running, async library.** Do not redesign code around request/response web lifecycles, controllers, per-request state, or blocking flows. `Discord` is a process runtime, not a web handler.
2. **Preserve layer boundaries.**
   - `Discord` runtime wires loop, logger, cache, HTTP, gateway, handlers, and root repositories.
   - `Parts` model Discord resources and expose typed magic attributes.
   - `Repositories` are typed collections plus REST/cache wrappers.
   - `Builders` own outbound payload construction and validation.
   - `WebSockets/Events` translate gateway payloads into cached objects and repository updates.
   - `DiscordCommandClient` is an optional higher-level feature layered on top of core `Discord`.
3. **Prefer existing abstractions over raw arrays and ad hoc helpers.** New Discord object data should usually become a `Part`, new collections should usually be a `Repository`, and new outbound payloads should usually be a `Builder`.
4. **Keep async semantics intact.** Production I/O stays Promise-based. Only tests use `wait()` to bridge async behavior into PHPUnit.
5. **Docblocks are part of API surface.** `@property`, `@property-read`, `@method`, `@since`, and `@link` blocks are relied on by generated docs and by developers navigating the magic property model.
6. **Use current library idioms.** Prefer `Factory::part()` / `Factory::repository()`, prefer `$part->save($reason)` over repository `save($part)` in user-facing paths, and prefer builder `->create($repository)` helpers where they exist.
7. **Do not move logic to the wrong layer.**
   - Permission gates belong close to the high-level operation on the `Part`.
   - Endpoint binding, REST verbs, and cache persistence belong in the `Repository`.
   - Gateway payload interpretation belongs in `WebSockets/Events`.
   - Fluent payload validation belongs in `Builders`.

## Skill map

| If task touches... | Load these skills first |
| --- | --- |
| `Discord.php`, startup, intents, cache, loop, gateway connection | `runtime-bootstrap-keeper` |
| `Parts/*`, domain modeling, mutators, typed nested data | `part-model-maintainer` |
| `Repository/*`, endpoint vars, cache, CRUD, fetch/save/delete | `repository-cache-keeper` |
| `WebSockets/Handlers.php`, `WebSockets/Event.php`, `WebSockets/Events/*` | `gateway-cache-sync-keeper` |
| `Builders/*`, `Builders/Components/*`, outbound payload rules | `builder-payload-smith` |
| subtype maps like `Channel::TYPES` or `Interaction::TYPES` | `type-map-keeper` |
| interactions, slash commands, resolved data, autocomplete, modals | `interaction-flow-keeper` |
| `DiscordCommandClient` or prefix-command behavior | `legacy-command-client-keeper` |
| tests, guides, docblocks, generated reference expectations | `async-test-and-doc-sync` |

---

## Skill: runtime-bootstrap-keeper

**Use when**

- touching `src/Discord/Discord.php`
- changing startup options, loop setup, cache configuration, gateway behavior, readiness, chunking, reconnect logic, or root repositories
- changing anything that affects process lifecycle rather than a single resource type

**Read first**

- `src/Discord/Discord.php`
- `src/Discord/WebSockets/Handlers.php`
- `src/Discord/WebSockets/Event.php`
- `src/Discord/Factory/Factory.php`
- `README.md`
- `guide/basics.rst`

**Mental model**

`Discord` is the orchestrator. It resolves options, wires infrastructural dependencies, creates the HTTP client, handler registry, factory, and top-level client part, connects to the gateway during construction, and only starts the loop when `run()` is called.

**Protect these invariants**

1. Construction is eager. `Discord::__construct()` does real setup work and calls `connectWs()`.
2. `run()` only starts the loop. It is not where dependency wiring happens.
3. CLI-only assumption stays explicit. The library warns when run outside `cli` or `micro`.
4. Options are normalized centrally with `OptionsResolver`.
5. Intent and capability arrays are folded into bitmasks at option resolution time.
6. Root repositories remain long-lived properties on the client, not ephemeral fetch helpers.
7. Critical gateway events must stay enabled unless explicitly disabled; disabling them has semantics, not only performance impact.
8. Large-guild member chunking is a first-class runtime concern; it must stay compatible with intent requirements.
9. Cache configuration and collection class configuration are repo-wide runtime services, not per-call knobs.

**Common patterns to preserve**

- constructor injects `loop`, `logger`, `http`, `factory`, `handlers`
- `Factory` is created once per client and reused everywhere
- gateway event handlers are selected through `Handlers`
- ready flow backfills guilds, then chunking, then ready emission
- runtime methods often coordinate multiple subsystems but still defer domain details to parts/repositories/events

**Do**

- normalize new options in `resolveOptions()`
- validate new intent-dependent behavior near option resolution or startup
- keep lifecycle flags (`connected`, `closing`, `reconnecting`, `emittedInit`) coherent
- preserve event-loop-friendly control flow
- reuse `Factory`, `Handlers`, `Endpoint`, and existing helper methods before introducing new runtime plumbing

**Do not**

- move domain mutation logic from parts/events into `Discord`
- make startup depend on request-time context or web frameworks
- add synchronous waits or blocking loops
- bypass handler registry by hardcoding event logic in multiple places

**Done when**

- new runtime behavior still fits `Discord` as orchestrator, not as domain owner
- option normalization, logging, and lifecycle transitions stay internally consistent
- any new event dependency is reflected in handler registration or disabled-event behavior

---

## Skill: part-model-maintainer

**Use when**

- adding or modifying any `Part`
- changing fillable attributes, computed properties, nested typed data, `save()`, `fetch()`, permission checks, or docblocks
- adding a new Discord object or extending an existing one

**Read first**

- `src/Discord/Parts/Part.php`
- `src/Discord/Parts/PartTrait.php`
- representative concrete parts:
  - `src/Discord/Parts/Guild/Guild.php`
  - `src/Discord/Parts/Channel/Channel.php`
  - `src/Discord/Parts/Channel/Message.php`
  - `src/Discord/Parts/User/User.php`
  - `src/Discord/Parts/Interactions/Interaction.php`

**Mental model**

A `Part` is the canonical in-memory model of a Discord resource. It stores raw API-shaped attributes, lazily exposes typed nested objects and repositories through magic access, tracks whether the entity already exists in Discord with `$created`, and delegates persistence to its originating repository.

**Protect these invariants**

1. Parts extend `Part`, inherit `PartTrait`, and use snake_case attribute keys matching Discord payloads.
2. `$fillable` is the source of truth for mass-assignment. If a field is missing there, `fill()` ignores it.
3. Nested typed values should be exposed through mutators and helpers, not left as raw arrays when a corresponding `Part` exists.
4. Lazy child repositories belong in `$repositories`; accessing them through magic properties should construct the right repository on demand.
5. Public/computed convenience attributes use `get{Studly}Attribute()` mutators and are documented with `@property-read`.
6. Typed nested setters use `set{Studly}Attribute()` when data must be normalized on write.
7. `getCreatableAttributes()`, `getUpdatableAttributes()`, `getRepository()`, `save()`, `fetch()`, and `getRepositoryAttributes()` are the semantic extension points for persistable parts.
8. `save()` is where high-level permission checks and special-case persistence routing live before repository delegation.
9. `createOf()` preserves `$created` linkage between parent and nested parts; use it for nested typed data.
10. PHPDoc and runtime magic surface must stay in sync.

**Helpers to prefer**

- `attributeCarbonHelper()` for timestamps
- `attributePartHelper()` for single nested parts
- `attributeCollectionHelper()` for homogeneous nested collections
- `attributeTypedCollectionHelper()` for runtime-dispatched subtype collections
- `makeOptionalAttributes()` for optional API fields that must only serialize when present
- `createOf()` for nested parts that should mirror parent `created` state

**Common semantic patterns**

- giant class-level `@property` surfaces describe raw, computed, and repository-backed attributes
- constants mirror Discord enum/flag values and often keep deprecated aliases for compatibility
- parts often expose both raw identifier fields (`guild_id`) and resolved relations (`guild`)
- channel-like, guild-like, and similar shared behaviors are moved into focused traits instead of deep inheritance
- permission-gated mutating methods live on parts because they understand the domain action being performed

**Do**

- add new Discord payload fields to `$fillable`
- add typed getters/setters when a field should become a `Part`, collection, `Carbon`, or computed convenience property
- update `@property` docs whenever the magic surface changes
- update `getRepositoryAttributes()` whenever child routes depend on parent IDs
- keep `getCreatableAttributes()` and `getUpdatableAttributes()` aligned with Discord API semantics, not just current tests

**Do not**

- bypass `$fillable` by stashing meaningful API state in ad hoc properties
- expose nested Discord objects as raw arrays when the repo already has a `Part` type
- serialize optional fields indiscriminately when the API distinguishes "missing" from `null`
- add permission checks to repositories if the semantic action belongs on the part
- forget that magic property names are part of public API

**Done when**

- raw API data, computed accessors, typed nested objects, repository access, persistence rules, and docblocks all tell the same story

---

## Skill: repository-cache-keeper

**Use when**

- changing any repository
- adding new REST endpoints, cache behavior, or nested repository bindings
- adjusting collection behavior for Discord-owned resources

**Read first**

- `src/Discord/Repository/AbstractRepository.php`
- `src/Discord/Repository/AbstractRepositoryTrait.php`
- representative repositories:
  - `src/Discord/Repository/GuildRepository.php`
  - `src/Discord/Repository/Guild/ChannelRepository.php`
  - `src/Discord/Repository/Channel/MessageRepository.php`

**Mental model**

A repository is both a typed collection and the persistence boundary for a family of parts. It knows endpoint templates, route variables, cache wrapper behavior, and how to hydrate or update parts. Repositories are where REST and cache coordination happens.

**Protect these invariants**

1. Every repository declares `$class`; most also declare `$endpoints`; some override `$discrim`.
2. `$vars` holds route-binding context like `guild_id`, `channel_id`, `message_id`; parts feed these through `getRepositoryAttributes()`.
3. Repository `create()` builds a local part but does not persist to Discord.
4. Repository `save()` decides POST vs PATCH based on `$part->created`.
5. Repository `delete()` and `fetch()` are Promise-based and keep cache state aligned.
6. Cache wrapper integration is per-repository and must stay transparent to callers.
7. Collection semantics still matter: repositories behave like typed collections even while wrapping async cache access.
8. Repository methods should return parts or repositories with meaning, not loose decoded payloads.

**Common semantic patterns**

- `freshen()` refreshes all cached data from REST and rewrites cache entries
- `fetch()` prefers in-memory or cached parts before REST unless forced fresh
- repository constructors may sanitize parent vars for route correctness
- nested repositories are bound by parent part IDs, not by ad hoc caller knowledge
- repositories often expose convenience methods for domain-specific endpoints but still cache typed parts

**Do**

- bind endpoints using `Endpoint` and `$vars`
- create or update cache entries whenever REST mutates canonical state
- keep repository return types typed through parts or repositories
- use repository-specific methods for domain operations that are broader than generic CRUD
- ensure child repositories can reconstruct correct route vars from their owning part

**Do not**

- move permission decisions from parts into repositories unless the operation is repository-native and not tied to a single part instance
- return raw REST payloads when a typed part exists
- add synchronous cache assumptions to async methods
- forget to keep cache, `items`, and route vars coherent

**Done when**

- repository remains the single source of truth for REST endpoint use and cache persistence for its part family

---

## Skill: gateway-cache-sync-keeper

**Use when**

- changing any gateway event behavior
- adding a new event class
- changing cache mutation, event return shapes, or handler registration

**Read first**

- `src/Discord/WebSockets/Handlers.php`
- `src/Discord/WebSockets/Event.php`
- relevant event class in `src/Discord/WebSockets/Events/*`
- relevant part/repository types that the event updates

**Mental model**

Gateway events are not passive notifications. In this repo, event handlers are responsible for translating raw dispatch payloads into typed parts, updating cached repositories, maintaining counters or relation state, caching related users/members, and returning the value that userland event listeners receive.

**Protect these invariants**

1. New gateway events must usually touch both `Event.php` and `Handlers.php`.
2. Event handlers return typed semantic payloads, not raw transport data, unless partial/raw return is intentional.
3. Cache mutation happens inside the event handler, close to payload interpretation.
4. When runtime subtype dispatch exists, use `TYPES` maps instead of hardcoded switch forests.
5. User and member caches are warmed through helper methods like `cacheUser()` and `cacheMember()`.
6. Message events preserve special behavior around stored messages, intent-gated content, old/new return pairs, and partial updates.
7. Guild and thread events preserve repository relationships and counts, not merely object hydration.

**Common semantic patterns**

- update events often return `[newPartOrData, oldPart]`
- delete events often return the removed cached part plus extra state, not only the payload id
- message create/update handlers maintain `last_message_id`, message caches, mention/user/member caches
- interaction handler avoids slow cache lookups that would delay interaction handling
- guild create handler is responsible for bulk hydration of related repositories

**Do**

- instantiate the right typed part for gateway payloads
- update all affected repositories, counts, and related object caches
- preserve existing event return shapes when extending behavior
- use async-friendly generator/yield flow where handlers already do
- audit side effects on parent/child relationships, not just the primary object

**Do not**

- update only one cache when several related repositories need coherence
- forget handler registration after adding a new event class
- replace typed part returns with decoded arrays without a strong compatibility reason
- add REST fetches where gateway payloads already contain enough data to update caches

**Done when**

- dispatch payload, typed parts, cache state, and emitted event payload all stay semantically aligned

---

## Skill: builder-payload-smith

**Use when**

- adding or changing builders
- adjusting outbound payload validation
- adding component or modal payload construction rules
- deciding whether a new public API should accept raw arrays or a builder

**Read first**

- `src/Discord/Builders/Builder.php`
- `src/Discord/Helpers/DynamicPropertyMutatorTrait.php`
- representative builders:
  - `src/Discord/Builders/MessageBuilder.php`
  - `src/Discord/Builders/ChannelBuilder.php`
  - `src/Discord/Builders/CommandBuilder.php`
  - `src/Discord/Builders/ModalBuilder.php`
- component base types under `src/Discord/Builders/Components/*`

**Mental model**

Builders are request-shape specialists. They validate what userland wants to send, encode it into the shape Discord expects, and deliberately stay separate from `Part` objects. Builders are the preferred home for outbound payload invariants.

**Protect these invariants**

1. Builders extend `Builder`, not `Part`.
2. Builders use `DynamicPropertyMutatorTrait`, which mirrors the part mutator pattern at property level.
3. Builders validate limits eagerly in setters or adders.
4. Builders implement `JsonSerializable` and own outbound array shape.
5. `fromPart()` is the canonical bridge from stored `Part` data to editable builder state.
6. Where supported, builders provide `create($repository)` helpers so callers do not manually juggle raw arrays.
7. Component-capable builders use `ComponentsTrait`; usage-specific validation stays in `addComponent()`.

**Common semantic patterns**

- fluent setters return `$this`
- builders keep nullable properties and omit unset optionals during `jsonSerialize()`
- payload validation mirrors Discord API rules: lengths, counts, valid enum values, valid component usage contexts
- builders sometimes accept both parts and scalars in setters, normalizing to IDs or raw payloads

**Do**

- put payload rules in builders instead of sprinkling array validation through parts or repositories
- preserve fluent setter style and `new()` factory methods
- keep `jsonSerialize()` shape faithful to Discord docs and existing builder conventions
- prefer typed component objects over unstructured component arrays

**Do not**

- make builders own cache or persistence behavior
- reintroduce raw array construction where a mature builder already exists
- let builder validation drift from actual API limits
- duplicate part mutator logic inside repositories

**Done when**

- outbound payloads are validated, serializable, and still clearly separated from in-memory resource models

---

## Skill: type-map-keeper

**Use when**

- adding a new channel, interaction, embed, or component subtype
- changing any `TYPES` lookup table
- touching code that dispatches by `type`, `component_type`, or similar runtime discriminator

**Read first**

- `src/Discord/Parts/Channel/Channel.php`
- `src/Discord/Parts/Interactions/Interaction.php`
- `src/Discord/Parts/Embed/Embed.php`
- `src/Discord/Parts/Channel/Message/Component.php`
- `src/Discord/Builders/Components/ComponentObject.php`

**Mental model**

This repo prefers runtime subtype maps over repeated conditionals. Payload discriminators select the concrete class once, then the rest of the system works with the right type.

**Protect these invariants**

1. Type maps keep a fallback entry at index `0` or equivalent safe default.
2. Builder and part representations for the same subtype family should stay conceptually aligned.
3. Event handlers and attribute helpers should consume type maps rather than duplicating branching logic.
4. New subtype classes should inherit from the same root family and preserve shared trait behavior where applicable.
5. Public constants remain the stable vocabulary for userland code.

**Do**

- extend the relevant `TYPES` map when introducing a new Discord subtype
- add any corresponding constants, docblocks, and helper methods
- check gateway events, attribute helpers, and builders that materialize the type family

**Do not**

- add a subtype class without wiring the dispatch map
- hardcode a one-off conditional in an event while leaving the central map stale
- forget the builder-side component or interaction mirror when the type family exists in both inbound and outbound flows

**Done when**

- runtime subtype dispatch works from every entry point that materializes the family

---

## Skill: interaction-flow-keeper

**Use when**

- changing interactions, slash commands, autocomplete, modal submit handling, resolved data, or command registration
- changing how builders and interaction parts cooperate

**Read first**

- `src/Discord/Parts/Interactions/Interaction.php`
- `src/Discord/WebSockets/Events/InteractionCreate.php`
- `src/Discord/Builders/CommandBuilder.php`
- `src/Discord/Builders/ModalBuilder.php`
- `src/Discord/Parts/Interactions/*`
- `guide/interactions.rst`

**Mental model**

Interactions are a typed inbound protocol layered on top of the gateway. The event handler must turn raw payloads into the right interaction part, cache resolved users/members/entitlements, and invoke registered command callbacks quickly enough for interaction timing constraints.

**Protect these invariants**

1. `Interaction::TYPES` chooses the concrete inbound subtype.
2. Resolved users/members/channels/roles should become typed objects or collections, not loose arrays.
3. Interaction handlers avoid unnecessary slow cache lookups that can delay responses.
4. Autocomplete and command execution routing stay tied to registered command abstractions, not duplicated in userland-facing code.
5. Builders for commands and modals remain the preferred outbound construction path.

**Do**

- preserve typed accessors on `Interaction`
- keep resolved-data caching aligned with guild/user caches
- keep modal and component usage constraints encoded in builders and component classes
- treat interaction response shapes as API contracts

**Do not**

- collapse typed interaction subclasses back into one generic untyped object
- block interaction flow on optional cache hydration
- leak message-command assumptions into slash-command code paths

**Done when**

- inbound interaction data, registered command routing, and outbound builder-based responses still form one coherent system

---

## Skill: legacy-command-client-keeper

**Use when**

- touching `DiscordCommandClient`
- modifying prefix-based command parsing, aliases, cooldowns, or help output
- maintaining compatibility for message-command bots built on the optional command layer

**Read first**

- `src/Discord/DiscordCommandClient.php`
- `src/Discord/CommandClient/Command.php`
- any related guide/example files under `examples/` or `guide/`

**Mental model**

The command client is intentionally a higher-level convenience layer built on top of the core async client. It should not redefine the core architecture. It listens to message events, parses prefixes, routes to command objects, and offers a help/cooldown/subcommand abstraction for message-based bots.

**Protect these invariants**

1. `DiscordCommandClient` extends `Discord`; it is an add-on, not a separate runtime stack.
2. Prefix parsing and alias resolution happen in the command layer, not in core message parts.
3. Command objects own help text, cooldown state, subcommand trees, and execution callables.
4. The help command is feature behavior of the command layer, not general library behavior.

**Do**

- keep command parsing localized to the command client and command objects
- preserve option resolution and case-insensitive behavior contracts
- keep help output and cooldown behavior tied to command metadata

**Do not**

- push prefix-command concerns into `Message`, `Channel`, `Discord`, or interaction code
- make command client changes that alter core runtime semantics

**Done when**

- prefix-command behavior still feels like a clean optional layer on top of the core client

---

## Skill: async-test-and-doc-sync

**Use when**

- adding or changing public behavior
- updating builder validation, part semantics, repository behavior, or docs
- touching anything user-facing enough that tests or docs should move with it

**Read first**

- `tests/functions.php`
- `tests/DiscordTestCase.php`
- representative unit tests under `tests/Builders` and `tests/Parts`
- `guide/`
- `README.md`
- `CONTRIBUTING.md`

**Mental model**

The repo mixes isolated PHPUnit unit tests with integration-style tests against a real Discord bot and channel. Docs are split between long-form guide content and API-reference-oriented docblocks. Keeping behavior, tests, and docs aligned is part of the maintenance job.

**Protect these invariants**

1. Integration tests use the real async runtime and the `wait()` helper; plain `TestCase` tests are better for isolated logic.
2. Public magic properties and repository methods should be reflected in docblocks.
3. Long-form guides and examples should only change when public behavior or recommended usage changes.
4. Builders and parts should have tests that assert semantic behavior, not just trivial setters.

**Do**

- add or update unit tests for isolated builder/part/repository logic when behavior changes
- use integration tests when behavior truly depends on Discord runtime interactions
- update docblocks whenever public magic attributes, repositories, or methods change
- update guide/docs/examples only when public guidance needs to change

**Do not**

- introduce blocking helpers into production code to satisfy tests
- rely on integration tests when a unit test can cover the logic safely
- leave docs advertising old usage when new patterns are preferred

**Done when**

- code, tests, and docs all describe the same behavior at the same abstraction level

---

## Final reminder

When a change feels awkward, check whether logic has drifted into the wrong layer. In this repo, most design bugs come from violating one of these seams:

- runtime vs domain model
- part vs repository
- repository vs gateway event
- part vs builder
- core client vs optional command layer
- raw array payloads vs typed parts/builders

If the seam stays clean, the change is usually on the right track.

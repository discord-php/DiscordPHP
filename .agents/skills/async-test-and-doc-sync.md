# Skill: async-test-and-doc-sync

Use this skill when adding or changing public behavior, updating builder validation or part semantics, writing or reviewing tests, updating PHPDoc blocks or guide pages, or touching anything user-facing enough that tests or docs should move with it.

This is alignment skill. Load it when the question is not "does the code work?" but "do tests prove it and do docs describe it?"

## Goal

Keep tests, PHPDoc, and long-form documentation synchronized with the public behavior of parts, repositories, builders, and helpers:

- unit tests assert semantic behavior, not incidental implementation
- integration tests prove real async flows against Discord
- PHPDoc blocks serve as contract surface for IDEs and generated reference
- guide and docs pages reflect current recommended usage
- changes to public behavior always travel with their companion test and doc updates

## Read in this order

1. `tests/functions.php` — `wait()` helper and `getMockDiscord()` factory
2. `tests/DiscordTestCase.php` — integration base class with real channel setup
3. `tests/bootstrap.php` — autoload, `.env` loading, singleton wiring
4. `phpunit.xml` — test suite configuration, coverage settings
5. Representative unit tests:
   - `tests/Builders/ModalBuilderTest.php`
   - `tests/FunctionsTest.php`
   - `tests/CollectionsTest.php`
6. Representative integration tests:
   - `tests/Parts/Channel/ChannelTest.php`
   - `tests/Parts/Embed/EmbedTest.php`
   - `tests/Parts/Channel/Message/MessageTest.php`
7. `guide/` — long-form RST documentation for parts, events, builders
8. `docs/` — Gatsby site source for published documentation
9. `README.md` — getting started, installation, basic usage
10. `CONTRIBUTING.md` — contributor workflow and code style expectations

## Core contract

Tests and docs in this repo are not afterthoughts. They form a contract surface:

- PHPDoc `@property` and `@property-read` annotations on parts are how IDEs and users discover magic properties. If code adds a fillable field or getter mutator without updating the docblock, the property is effectively invisible to consumers.
- Unit tests using plain `PHPUnit\Framework\TestCase` prove that isolated logic works without needing Discord credentials or a running event loop. These are fast and reliable.
- Integration tests extending `DiscordTestCase` prove that async flows work against real Discord infrastructure. These require environment variables and a live bot token.
- Guide pages in `guide/` and Gatsby pages in `docs/` describe recommended patterns. They should only change when public behavior or preferred usage actually changes — not for internal refactors.

If a change touches public behavior but skips one of these surfaces, the contract is incomplete.

## Unit tests vs integration tests

### When to use `TestCase`

Use plain `PHPUnit\Framework\TestCase` when the logic under test is isolated from Discord I/O:

- builder validation limits (content length, component counts, enum values)
- helper function behavior (`contains()`, `studly()`, `escapeMarkdown()`, `poly_strlen()`)
- collection operations (`push()`, `map()`, `filter()`, `from()`)
- part attribute hydration from raw arrays using `getMockDiscord()`
- serialization shape from `jsonSerialize()` on builders

These tests need no token, no loop, and no network. They run in milliseconds.

Example: `tests/Builders/ModalBuilderTest.php` uses `$this->expectException(\LogicException::class)` and `str_repeat('a', 101)` to verify title length validation.

### When to use `DiscordTestCase`

Use `DiscordTestCase` only when the test must interact with real Discord infrastructure — sending/editing/deleting messages, pinning, creating invites, fetching message history, verifying embed hydration, or testing repository `fetch()`/`freshen()` against live API.

These tests require `DISCORD_TOKEN`, `TEST_CHANNEL`, and `TEST_CHANNEL_NAME` in `.env` or environment. `DiscordSingleton` shares a single connected client across the suite. Tests `markTestSkipped` if credentials are missing.

### The `getMockDiscord()` factory

Defined in `tests/functions.php`. Creates a minimal `Discord` instance with empty token and `NullLogger` — suitable for constructing parts and testing attribute access without connecting to gateway.

## Test suite organization

### Directory layout

Test files mirror the source structure. Builder tests live in `tests/Builders/`, part tests in `tests/Parts/{Family}/`, and utility tests in `tests/` root. Infrastructure files (`bootstrap.php`, `functions.php`, `DiscordSingleton.php`, `DiscordTestCase.php`) live at the `tests/` root.

### Where to place new tests

- Builder tests → `tests/Builders/{BuilderName}Test.php`
- Part tests → `tests/Parts/{Family}/{PartName}Test.php`
- Helper/utility tests → `tests/` root
- Unit tests extend `TestCase`, integration tests extend `DiscordTestCase`

### phpunit.xml

Discovers all `*Test.php` under `tests/` recursively. Coverage tracks `src/`. Bootstrap is `tests/bootstrap.php`.

## Async testing patterns

### The `wait()` bridge

`wait()` in `tests/functions.php` bridges Promise-based async code into synchronous PHPUnit assertions:

```php
function wait(callable $callback, float $timeout = TIMEOUT, ?callable $timeoutFn = null)
```

It works by scheduling `$callback` on the ReactPHP loop via `futureTick()`, passing a `$resolve` callable that stops the loop and captures the result, adding a timeout timer (default 10s), running the loop synchronously, and re-throwing any captured exception after loop stops.

### Common assertion patterns in integration tests

**Promise chain with assertion then resolve:**

```php
return wait(function (Discord $discord, $resolve) {
    $this->channel()->sendMessage('test content')
        ->then(fn (Message $m) => $this->assertEquals('test content', $m->content))
        ->then($resolve, $resolve);
});
```

**Custom timeout with fallback:**

```php
return wait(function (Discord $discord, $resolve) {
    // ... async work ...
}, 10, fn () => $this->markTestIncomplete('Hit rate limit.'));
```

Key rules:

- always pass `$resolve` as both fulfillment and rejection handler at the end of the chain
- the `$callback` receives `(Discord $discord, callable $resolve)` — use `$discord` for client access
- return the `wait()` call from the test method so PHPUnit tracks it
- default timeout is 10 seconds (`TIMEOUT` constant)

## PHPDoc as contract surface

### What to document

Every part class should have class-level docblock annotations for `@property` (read/write magic properties from `$fillable`), `@property-read` (computed properties from mutators or repos), `@method` (delegated magic methods), `@since` (version introduced), and `@link` (Discord API docs URL).

### Why this matters

PHPDoc in this repo serves three purposes: **IDE autocompletion** (consumers rely on `@property` since all access goes through `__get()`), **generated reference** (docs tooling reads annotations), and **static analysis** (Mago uses docblocks for type checking).

### When to update docblocks

- new field added to `$fillable` → add `@property` with type
- new getter mutator added → add `@property-read` with return type
- new repository exposed in `$repositories` → add `@property-read` for the repository type
- field removed or deprecated → update or remove annotation
- type changed (e.g., `string` to `?string`) → update annotation

### Example pattern

```php
/**
 * @property string      $id
 * @property string      $name
 * @property-read Carbon $created_at
 * @property-read MemberRepository $members
 * @link https://discord.com/developers/docs/resources/guild
 */
class Guild extends Part
```

## Guide and documentation structure

### `guide/` — long-form RST content

User-facing guides organized by topic: `basics.rst` (getting started, intents), `parts/` (per-resource docs), `events/` (gateway handling), `repositories.rst`, `message_builder.rst`, `components.rst`, `interactions.rst`, `permissions.rst`, `collection.rst`, `faq.rst`.

### `docs/` — Gatsby site

Published documentation website source. Build with `cd docs && yarn install && yarn build`.

### `README.md`

Installation, requirements, basic bot example. Update when minimum PHP version, major dependencies, or getting-started flow changes.

## When docs must change

Docs should change when:

- a new public method, property, or repository is added that users will call
- preferred usage pattern changes (e.g., builder replaces raw array)
- a method signature or return type changes
- a feature is deprecated and users need migration guidance
- default behavior changes in a way that affects existing bots

Docs should **not** change when:

- internal refactoring preserves all public behavior
- test infrastructure changes
- cache implementation details shift without affecting public API
- gateway event internals change without altering emitted shapes

## Running tests and checks

| Purpose | Command |
| --- | --- |
| Run PHPUnit suite | `composer unit` |
| Static analysis | `composer run-script mago-lint` |
| Code style fixer | `composer run-script cs` |
| Non-mutating style check | `./vendor/bin/pint --test --config ./pint.json ./src` |
| Docs site build | `cd docs && yarn install && yarn build` |

### Integration test environment

Integration tests require `.env` or shell variables: `DISCORD_TOKEN` (bot token), `TEST_CHANNEL` (channel ID), `TEST_CHANNEL_NAME` (channel name). When absent, `DiscordSingleton` falls back to `getMockDiscord()` and integration tests skip.

### Running subsets

Use `--filter` to target specific tests: `./vendor/bin/phpunit --filter ModalBuilderTest`

## What to test

### Builder tests should assert

- validation boundary cases (max length, max count, invalid enum)
- correct exception types and messages for invalid input
- `jsonSerialize()` output shape matches Discord API expectations
- `new()` factory wires arguments correctly
- `fromPart()` round-trip preserves meaningful fields
- optional fields omitted when unset

### Part tests should assert

- getter mutators return correct types (Carbon, Collection, nested Part)
- attribute hydration from raw Discord payload arrays
- `getCreatableAttributes()` / `getUpdatableAttributes()` include correct fields
- permission and semantic guard behavior in `save()` overrides
- computed properties resolve correctly from related data

### Integration tests should assert

- end-to-end flows complete through real Discord API
- returned objects are correctly typed instances
- repository operations (fetch, freshen, save) produce expected state
- message operations (send, edit, delete, pin) work as documented

### What not to test

- trivial getters/setters with no logic
- internal cache mechanics (test through public behavior instead)
- Discord API behavior itself (test your code's handling of responses)

## Smells

Stop if you see:

- new public magic property with no `@property` docblock entry
- builder with validation logic but no test for boundary cases
- integration test that could be a unit test (no real Discord interaction needed)
- unit test extending `DiscordTestCase` when `TestCase` would suffice
- `wait()` used in a test that never performs async I/O
- guide page updated for internal refactor that did not change public behavior
- public method added with no test and no docblock
- test asserting exact internal array structure instead of semantic behavior
- `getMockDiscord()` used where real client interaction is actually needed

## Checklist before commit

- [ ] New public properties have `@property` or `@property-read` in class docblock
- [ ] Builder validation has boundary test cases (valid edge, invalid edge, exception type)
- [ ] Integration tests use `wait()` pattern with proper `$resolve` chaining
- [ ] Unit tests use plain `TestCase`, not `DiscordTestCase`
- [ ] Test file placed in correct directory mirroring source structure
- [ ] Guide pages updated if preferred usage or public contract changed
- [ ] README updated if installation, requirements, or getting-started flow changed
- [ ] `composer unit` passes
- [ ] `composer run-script mago-lint` reports no new issues
- [ ] No tests left that assert implementation details instead of behavior

## Bottom line

Tests prove behavior, docblocks declare surface, guides teach usage — if any of these drift from the code, users and tooling lose trust in the library.

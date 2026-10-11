# Component embed compatibility milestone

Source reviewed: remote `master` at `ee73786f2ee8cd94120abd3f8f666cf13544b60b`.
The main checkout's unrelated OpenAPI edits and intentional Composer path repository
were preserved. Work is isolated on `codex/component-embeds`.

Evidence read on October 10, 2026:

- [October 5 changelog](https://docs.discord.com/developers/change-log#component-embeds-and-link-preview-docs)
- [Component embeds](https://docs.discord.com/developers/link-previews/component-embeds)
- [Link previews](https://docs.discord.com/developers/link-previews/overview)

This is a documented website link-preview contract, not a bot authoring API.
Discord notes that link previews are subject to change. A received message uses
embed type `components`, with one Container in its `components` array. Website
authoring uses a different envelope: `{"component": {"type": 17, "components": [...]}}`.
No interaction or bot is required. The library runtime remains CLI-only; the
website builder merely generates JSON/HTML and does not construct a client.

The inherited embed type map covers message REST hydration, gateway create/update,
references, interaction message hydration and forwarded snapshots. Fixture tests
prove direct object/array payloads, REST fetch, create/update cache state and
snapshot behavior. Associative nested payloads now use the same discriminator as
decoded objects in the shared typed-collection helper and Section accessory getter.
Only `EmbedComponents` adds `components` to inherited fillable fields, preserving
rich embed serialization. Received component previews cannot enter MessageBuilder
bot/webhook embed payloads, including via `fromPart()`.

`ComponentEmbedBuilder` owns website limits and script encoding, reusing existing
Container/component builders without adding a repository, transport or message
creation helper. It snapshots trees, enforces supported placements and button keys,
counts accessories and global gallery items, and validates the final escaped JSON
byte length. Input Parts are deliberately rejected because fetched media fields
do not match authoring fields. It never downloads assets; MIME, crawler access,
rendering and the ten-second fetch deadline remain deployment checks. Button URLs
allow 512 characters; media URLs allow 2,048. File suffix guards allow gallery video
and image thumbnails, reject unsupported extensions (including SVG/PDF), and permit
extensionless URLs whose actual format must be verified separately. Guide fallback
markup reuses the existing README's DiscordPHP community PNG banner. No website,
hosting settings, brand files or credentials were changed.

Dependencies: fresh worktree resolution uses `discord-php/http` **v10.9.8**
at `c4430613366b86937b985791baa000ec30e812d8`.
The preserved main checkout uses `dev-master` via `D:/GitHub/DiscordPHP-HTTP`,
path reference `6bb6aec802945bb2bd77edc06a1c4fa3af38f100`.

OpenAPI evidence: the latest existing OpenAPI Check run at review time was
[38052322548](https://github.com/discord-php/DiscordPHP/actions/runs/38052322548),
successful at source `580c66a1d8a37f68cb570fd0e20497ac1f516f52`. No open issue
labelled `openapi` was found. The existing checker on remote master does not yet
support the main checkout handoff's `--report` option; that invocation was rejected.
Running `php scripts/openapi-check.php` succeeded (exit **0**): 246 operations,
243 implemented, 3 known gaps, 0 new. Live spec and baseline both reference
`1ff2dee3677fabef7b49f942bbae93d06c0c9f2c` (October 8). The baseline is unchanged.
Schema coverage does not establish component-preview rendering or inbound support.

Verification commands (empty `DISCORD_TOKEN`, no `.env` copied):

```powershell
$env:DISCORD_TOKEN = ''
php -d xdebug.mode=off vendor/bin/phpunit --filter=ComponentEmbed
php -d xdebug.mode=off vendor/bin/phpunit
php vendor/bin/php-cs-fixer fix --dry-run --diff
composer run-script mago-lint
php scripts/component-embed-fixture.php > .tools/component-embeds/head.html
php scripts/component-embed-fixture.php --unicode > .tools/component-embeds/unicode.html
```

The installed `discord-component-link-previews/scripts/check_preview.py` independently
passed both emitted HTML heads using the verified bundled Python 3.12.14 interpreter.
The baseline head has 418 JSON bytes, the adversarial Unicode/quotes/closing-script
head has 470; both have 5 components and 1 gallery item. This is inline-only local
validation, not linked-document or live Discord rendering evidence.

Actual observed test and CI counts are recorded in the PR description against the
final source commit, rather than treated as assertions in this document. Follow-up
website adoption and crawler/debugger checks require a separately authorized site
milestone. OAuth2, sessions, lobbies and identity internals belong to another chat.
No merge, publishing, hosting changes, live bot tests or messages to people.

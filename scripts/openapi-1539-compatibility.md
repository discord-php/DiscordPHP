# OpenAPI issue 1539 compatibility review

Reviewed 2026-10-21 for [issue #1539](https://github.com/discord-php/DiscordPHP/issues/1539), separately from the optional RPC foundation.

## Inputs

- Core base: `2ac48efee9dc507bd8100f80601bc139eaed9a04`.
- HTTP package: `discord-php/http` v10.9.8, revision `c4430613366b86937b985791baa000ec30e812d8`. No endpoint changes are needed.
- Original baseline: `1ff2dee3677fabef7b49f942bbae93d06c0c9f2c` (2026-10-08).
- Reviewed stable and preview specs: [`bf93257f85d62054a0a6982d32f6bcb724abd015`](https://github.com/discord/discord-api-spec/commit/bf93257f85d62054a0a6982d32f6bcb724abd015) (2026-10-20).
- Original [OpenAPI run and handoff artifact](https://github.com/discord-php/DiscordPHP/actions/runs/39594771061): source is the core base above; checker exit 1. Eleven schema changes, no new operations or unsupported routes. All eleven changes are present in the stable spec.

## Disposition of every finding

| Schema changes | Implementation and regression evidence |
| --- | --- |
| New `MessageEmbedAudioResponse`, new `RichEmbedAudio`, `MessageEmbedResponse.audio`, `RichEmbed.audio` | New nested `Audio` Part and `Embed.audio` fillable, PHPDoc and hydration helper. `EmbedAudioTest` covers received messages, mocked REST fetch/send, missing/null audio, created-state linkage and metadata preservation. `EmbedAudioBuilderTest` covers nullable authored fields, exact character limits, invalid shapes/types, empty objects, snapshots and `fromPart()` validation. MessageBuilder sends only `url` and `description`, retaining response metadata on the received Part. |
| `ApplicationResponse.conjure_project_id`, `PrivateApplicationResponse.conjure_project_id`, `InviteApplicationResponse.conjure_project_id` | Existing `Application` field and PHPDoc from #1538. Public, private and partial application payloads share that Part. Invite `target_application` uses the same hydration helper. `ApplicationTest` and `OpenApiSchemaChangesTest` cover present and absent values, including nested invites. |
| `ConnectedAccountProviders.LETTERBOXD` | `Connection.type` is an unrestricted string; there is no closed enum to expand. `OpenApiSchemaChangesTest` verifies `letterboxd` hydration and serialization. |
| `SearchMessageResponse.author_restriction_count`, `.source_author_restriction_count`, `.forward_source_author_id` | Existing Message fillable fields and PHPDoc from #1538. `MessageTest::testSearchResultFieldsAreKept` covers IDs, positive and zero counts, and an absent field. |

## Baseline and verification

After reviewing the changes and running the focused offline tests, `composer openapi:update` records the reviewed spec. Only the spec URL, commit and date change; the three deliberately unsupported deprecated pin routes and their reasons are unchanged. A subsequent `composer openapi -- --report=...` exits 0. This does not claim complete API coverage.

The Windows PHP HTTPS stream could not download raw GitHub files. The same immutable stable, preview and historic spec documents were retrieved through GitHub's contents API and placed in the checker's existing per-commit cache. No checker or TLS behavior was changed. The checker independently resolves the live spec commit; retain JSON findings and original run metadata as task evidence.

Run tests with `DISCORD_TOKEN` explicitly empty in the PHP process. CI owns the Linux full suites, syntax checks and style checks. No live Discord messages, authentication or playback are tested. Schema support and recorded HTTP payloads do not establish server acceptance or client playback.

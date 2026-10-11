Provisional token renewal evidence
==================================

This milestone is stacked on draft PR #1522, branch
``codex/social-sdk-lobby-cache``, verified head
``efdcea153f9079ca5bc55f21c6cc564555f19f4e``. Do not merge independently.

Source checked October 10, 2026:
https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/managing-accounts
(page changelog October 5, 2026). Backend provisional tokens last seven days;
Public Client tokens last one hour. Renew through the original issuance method.
Server OIDC refresh-token grants are deprecated; obtain a fresh provider token.
This policy comes from the development guide, not a change to the OpenAPI
baseline. Runtime token expiry continues to use Discord's ``expires_in``.

Offline tests cover issuance classification, serialization through Array,
React cache and PSR-16 stores, restart and in-memory resume, expiry leeway,
ordinary OAuth refresh, legacy unknown-origin compatibility, and rejection
without provider replay or a deprecated grant. These use injected transports
and temporary in-memory storage; no live Discord/provider credentials.

A review branch push triggers only offline Tests; PR creation additionally
checks Version. Docs deployment requires master or a published release; the
site workflow requires a published release; live Unit Tests require manual
dispatch. This milestone changes none of those triggers.

Also checked the External Credentials Exchange guide (July 14, 2026 changelog),
which explicitly documents a returned OIDC refresh token and deprecation:
https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/external-credentials-exchange

Child classification preserves the existing documented non-refreshable child
contract: the Publisher Level Account Linking guide instructs re-exchange with
a valid parent token, not OAuth refresh:
https://docs.discord.com/developers/discord-social-sdk/development-guides/publisher-level-account-linking

Local verification on PHP 8.5.9: affected OAuth suite passes (54 tests,
255 assertions); focused php-cs-fixer dry run passes for all six PHP files.
Mago 1.52.0 repository lint reports 2091 issues (211 errors, 1350 warnings,
9 notes, 521 help messages). The three changed source files have exactly the
same normalized diagnostics as #1522's verified head: 12 issues (5 errors,
6 warnings, 1 help), with no added or removed diagnostics. The source baseline
was not changed. Full-suite and exact-head GitHub CI results are recorded in
the PR description after verification.

# DiscordPHP skills

Task-scoped guides for building and maintaining bots and API libraries on top of
DiscordPHP. Each `<name>/SKILL.md` has YAML frontmatter (`name`, `description`)
and a self-contained body; an agent loads one when its `description` matches the
task at hand.

| Skill | Use it when |
|---|---|
| [`discord-php-extension`](discord-php-extension/SKILL.md) | Scaffolding a new bot / API-library-on-DiscordPHP, or working across the existing `DiscordPHP-{MTG,NHA,Sabacc}` extensions — repo layout, the client subclass, the `Http`/`Endpoint`/`Request` trio, JSON state store, the `init` + `application-init` boot guard, composer path-repo wiring, the CRLF/diff gotcha. |
| [`discord-php-interactions`](discord-php-interactions/SKILL.md) | Adding or changing any `/command`, button or select — global command trees with subcommands, user- **and** guild-installable commands usable in DMs / group DMs / guilds, Components V2, the two ways to route component clicks, permission-safe replies. |
| [`discord-php-bot-security`](discord-php-bot-security/SKILL.md) | Reviewing an HTTP client, error handler, OAuth/webhook endpoint, or a repo before publishing — token-leak checklist, constant-time comparison, crypto-random state, what never to log. |

These are derived from patterns already in the ecosystem; when the code and a
skill disagree, the code is authoritative — update the skill.

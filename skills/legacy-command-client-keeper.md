# Skill: legacy-command-client-keeper

Use this skill when work touches:

- `src/Discord/DiscordCommandClient.php`
- `src/Discord/CommandClient/Command.php`

This is optional-prefix-command skill. Load it when maintaining the message-command layer built on top of core `Discord`.

## Goal

Keep prefix-command behavior as a clean optional layer:

- built on top of `Discord`
- driven by message events
- owning its own parsing, aliases, subcommands, cooldowns, and help behavior
- separate from application-command interaction flow

## Read in this order

1. `src/Discord/DiscordCommandClient.php`
2. `src/Discord/CommandClient/Command.php`
3. Example or guide material using command client, if public behavior changes
4. Interaction command path only if change risks cross-layer leakage:
   - `src/Discord/Helpers/RegisteredCommand.php`
   - `src/Discord/WebSockets/Events/InteractionCreate.php`

## Core contract

`DiscordCommandClient` extends `Discord`, but it does not replace core client architecture. It layers:

- command-specific options
- prefix detection
- alias maps
- command registry
- default help command
- message event routing into command callbacks

The command layer should stay optional and message-driven.

## Main responsibilities by class

### `DiscordCommandClient`

Owns:

- command-client option resolution
- wiring message event listeners during init
- prefix and mention-prefix expansion
- top-level command and alias registry
- default help command behavior

### `CommandClient\Command`

Owns:

- one command's callback
- subcommand tree
- alias mapping for subcommands
- help metadata
- cooldown tracking and cooldown messaging

If code does not clearly belong to one of those two, re-check whether it belongs in core `Discord` instead.

## Option rules

`DiscordCommandClient` uses `OptionsResolver` for its own layer. Preserve existing semantics around:

- `prefix`
- `prefixes`
- `name`
- `description`
- `defaultHelpCommand`
- `discordOptions`
- `caseInsensitiveCommands`
- `internalRejectedPromiseHandler`

Command-client options are not core runtime options. Keep them isolated unless a true cross-layer dependency exists.

## Parsing rules

Message-command flow today:

1. command client listens on init
2. prefix strings get mention placeholders expanded
3. on message event, ignore self messages
4. `checkForPrefix()` strips prefix
5. args parsed via `str_getcsv(..., ' ', '\"', '\\\\')`
6. command name resolved through commands then aliases
7. command callback invoked
8. string return values become reply messages

If changing parsing, preserve expectations around:

- mention prefixes
- quoted arguments
- alias lookup
- case-insensitive mode

## Help-system rules

The default help command is not incidental. It is part of command-client feature set.

Preserve:

- per-command descriptions and long descriptions
- usage text
- alias display
- subcommand display
- embed-based help formatting

If help behavior changes, inspect both top-level help and per-command help.

## Cooldown and subcommand rules

`CommandClient\Command` carries its own operational semantics:

- cooldowns keyed by author id
- subcommands recurse
- alias lookup for subcommands
- help visibility controlled by option metadata

Do not move cooldown logic into unrelated core message or user models.

## Boundaries with interaction commands

This layer is not the same as application commands.

Keep these separate:

- prefix commands use `DiscordCommandClient` + `CommandClient\Command`
- application commands use `Discord::listenCommand()` + `RegisteredCommand` + interaction events

Do not try to force one abstraction to power both unless you intend a broad architectural redesign.

Shared naming concepts are fine. Shared execution path is not current repo design.

## Error-handling rules

Internal rejected promises use `internalRejectedPromiseHandler`. Preserve that pattern rather than sprinkling silent catches or inconsistent logger behavior through callbacks.

If user-facing callback returns string, reply behavior should remain simple and unsurprising.

## Existing patterns worth copying

- top-level command registration through `registerCommand()`
- alias registration separated from command registration
- subcommand tree managed inside `Command`
- command options resolved once, then carried as help/cooldown metadata

## Smells

Stop if you see:

- prefix-command concerns moving into `Message`, `Channel`, `Discord`, or interaction code
- alias or cooldown state stored outside command layer
- parsing changes that break quoted args or mention prefixes without explicit intent
- help system logic duplicated in multiple places
- interaction-command abstractions merged into prefix-command path

## Checklist before commit

- command-client options still resolve cleanly
- prefix expansion and parsing still intentional
- command and alias registry behavior still coherent
- subcommand recursion still works
- cooldown behavior still belongs to command objects
- default help command still reflects command metadata
- no leakage into interaction command layer unless explicitly intended

## Bottom line

Legacy command client is convenience layer, not core runtime. Keep it self-contained, predictable, and clearly separate from slash-command interactions.

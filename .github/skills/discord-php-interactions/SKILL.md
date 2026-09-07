---
name: discord-php-interactions
description: Build DiscordPHP slash commands and message components — global command trees with subcommands, user- AND guild-installable commands usable in DMs / group DMs / guild channels, Components V2 (Container / TextDisplay / ActionRow / Button / StringSelect), the two ways to route component clicks, and keeping every reply permission-safe. Use when adding or changing any `/command`, button, or select.
---

# DiscordPHP Interactions Skill

Verified against `team-reflex/discord-php` dev-master (Discord API v10) while
building `DiscordPHP-Sabacc` and reviewing `DiscordPHP-MTG`.

## Registering a global command with subcommands

```php
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\{Command, Option, Choice};
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\OAuth\Application;

$sub = fn (string $n, string $d) => (new Option($bot))->setName($n)->setDescription($d)->setType(Option::SUB_COMMAND);
$opt = fn (string $n, string $d, int $t, bool $req = false) => (new Option($bot))->setName($n)->setDescription($d)->setType($t)->setRequired($req);

$builder = CommandBuilder::new()
    ->setName('foo')->setType(Command::CHAT_INPUT)->setDescription('…')
    ->setContext([                                   // where it can be used
        Interaction::CONTEXT_TYPE_GUILD,             // 0
        Interaction::CONTEXT_TYPE_BOT_DM,            // 1
        Interaction::CONTEXT_TYPE_PRIVATE_CHANNEL,   // 2  (group DMs)
    ])
    ->addIntegrationType(Application::INTEGRATION_TYPE_GUILD_INSTALL) // 0
    ->addIntegrationType(Application::INTEGRATION_TYPE_USER_INSTALL)  // 1
    ->addOption(
        $sub('play', 'Start a game.')
            ->addOption($opt('opponent', 'Challenge a member.', Option::USER))
            ->addOption(
                (new Option($bot))->setName('difficulty')->setDescription('…')->setType(Option::STRING)
                    ->addChoice((new Choice($bot))->setName('Easy')->setValue('easy'))
            )
    )
    ->addOption($sub('wallet', 'Show your balance.'));

// Only create if missing (creating is a rate-limited write):
if ($commandRepo->get('name', 'foo') === null) {
    $builder->create($commandRepo)->save('foo command');
}
$bot->listenCommand('foo', fn (Interaction $i) => $handler->dispatch($i));
```

Option type constants: `SUB_COMMAND=1`, `SUB_COMMAND_GROUP=2`, `STRING=3`,
`INTEGER=4`, `BOOLEAN=5`, `USER=6`, `NUMBER=10`. Parts are constructed
`new Option($discord)` / `new Choice($discord)` (or `$bot->getFactory()->part(...)`).

**Enabling User Install**: also toggle *User Install* under *Installation* in the
Developer Portal, or the `INTEGRATION_TYPE_USER_INSTALL` on the command is
ignored.

### Reading the invocation

```php
$sub  = $interaction->data->options->first();
$name = $sub?->name;
$args = [];
foreach ($sub?->options ?? [] as $o) { $args[$o->name] = $o->value; }
```

## Responding — and staying inside the caller's permissions

A user-installed command can run in a channel the **app is not a member of**. Only
*interaction-scoped* responses work there, and they inherently respect what the
invoking user can do. Use:

| Method | Use for |
|---|---|
| `$interaction->respondWithMessage($builder, ephemeral: true)` | first reply to a slash command |
| `$interaction->updateMessage($builder)` | edit the message a **button/select** is on |
| `$interaction->sendFollowUpMessage($builder, ephemeral: true)` | extra messages after the first |
| `$interaction->acknowledgeWithResponse(true)` then `updateOriginalResponse(...)` | when the work takes >3s |

Do **not** try to `$channel->sendMessage(...)` for gameplay output in a
user-install context — front everything through the interaction. Constrain
mentions with `MessageBuilder::new()->setAllowedMentions(AllowedMentions::new()->setParse(['users']))`
(or `AllowedMentions::none()`), never let role/`@everyone` pings through.

## Components V2

Available builders (`Discord\Builders\Components\*`): `Container`, `TextDisplay`,
`Separator`, `Section`, `ActionRow`, `Button`, `StringSelect` (+ `Option`),
`MediaGallery`, `Thumbnail`, `File`. Compose:

```php
$builder->addComponent(
    Container::new()
        ->addComponent(TextDisplay::new("## Board\n…"))
        ->addComponent(Separator::new())
);
$builder->addComponent(ActionRow::new()->addComponents([
    Button::new(Button::STYLE_PRIMARY, $customId)->setLabel('Draw'),
    Button::new(Button::STYLE_DANGER, $forfeitId)->setLabel('Forfeit'),
]));
```

Button styles: `STYLE_PRIMARY=1`, `SECONDARY=2`, `SUCCESS=3`, `DANGER=4`,
`LINK=5`, `PREMIUM=6`.

## Routing component clicks — two approaches

**A. Per-component listener** (`Button::setListener` / `SelectMenu::setListener`):
`->setListener(callable $cb, Discord $discord, bool $oneOff = false, ?float $timeout = null)`.
Simple, but each render attaches a fresh `INTERACTION_CREATE` listener; with a
long-lived interactive message you accumulate listeners. Good for one-shot
buttons (MTG uses it with `oneOff: true`, `timeout: 300`).

**B. Stable custom_ids + one dispatcher** (Sabacc uses this): give components ids
like `"foo|<matchId>|draw"` and route centrally:

```php
$bot->on(Discord\WebSockets\Event::INTERACTION_CREATE, function (Interaction $i) use ($bot) {
    if ($i->type !== Interaction::TYPE_MESSAGE_COMPONENT) return;
    $id = (string) ($i->data->custom_id ?? '');
    if (! str_starts_with($id, 'foo|')) return;
    $bot->session(explode('|', $id)[1])?->handle($i);
});
```

No listener leak; survives re-renders. For a select, fold every choice into the
option `value` (`"handIndex:sign:magnitude"`) so one interaction carries the whole
move and you need no follow-up prompt. `$i->data->values[0]` holds it.

**Never put a secret in a `custom_id`** — it is sent to Discord and visible in the
client. Capture tokens in the server-side listener closure instead.

## Validate the actor

Buttons are clickable by anyone who can see the message. In every handler check
`$match->seatOf($interaction->user->id)` (or equivalent) and reply ephemerally
"not your turn" / "you are not in this match" rather than assuming the clicker is
authorised.

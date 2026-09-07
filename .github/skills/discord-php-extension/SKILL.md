---
name: discord-php-extension
description: Scaffold and maintain a DiscordPHP-based bot or an API-library-on-DiscordPHP (the DiscordPHP-MTG / DiscordPHP-NHA / DiscordPHP-Sabacc pattern) — client subclass, third-party HTTP layer, Parts, repositories, JSON state store, the init/application-init boot guard, composer path-repo wiring, and the CRLF/diff gotcha. Use when creating a new extension repo or working across the existing ones.
---

# DiscordPHP Extension / Bot Skill

Distilled from the `discord-php/DiscordPHP-{MTG,NHA,Sabacc}` extensions, which all
follow the same shape: a `MessageCommandClient` subclass that also speaks to some
third-party REST API and/or persists per-user state.

## Repository layout

```
src/<Ns>/
  <Ns>.php              MessageCommandClient subclass; wires the HTTP client,
                        the Client part, and any services (Store, Shop, engine)
  Client.php            (only if you add repositories) extends
                        Discord\Parts\User\Client, adds entries to $repositories
  Http/
    Http.php            implements HttpInterface, `use HttpTrait`; points BASE_URL
                        at the third-party API
    Endpoint.php        implements EndpointInterface, `use EndpointTrait`; the
                        route table as `const`s with `:param` placeholders
    Request.php         extends Discord\Http\Request; overrides getUrl() to
                        prepend Http::BASE_URL
  Repository/           AbstractRepository + one per API resource (optional)
  Parts/               one Part subclass per API object; $fillable + get*Attribute()
  <Ns>Trait.php         pure presentation/helpers (no API, no I/O)
bot.php                boot + slash-command registration + component routing
tests/                 pure-logic tests; no live gateway
composer.json, .php-cs-fixer.dist.php, pint.json, phpunit.xml, env.example
```

Keep game/business logic in a namespace that imports **nothing** from `Discord\` so
it is unit-testable in isolation (see `Sabacc\Pazaak`).

## The client subclass

```php
class Foo extends \Discord\MessageCommandClient
{
    protected Http $foo_http;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->foo_http = new Http('', $this->loop, $options['logger'] ?? new NullLogger(), new React($this->loop, $options['socket_options'] ?? []));
        // If you added repositories:
        $this->client = $this->factory->part(Client::class, (array) $this->client);
    }
}
```

- **Never** pass the Discord bot token to a third-party API client. `MTG` passed
  `'Bot '.$this->token` into its `Http` and leaked the token on every request —
  pass `''` and only add an `Authorization` header when a value is actually set.
- Default the `Http` logger arg to `new NullLogger()`, not `null` (the trait
  type-hints `LoggerInterface`).
- `$options['socket_options']` → `new React($this->loop, ...)` lets callers set
  `dns` etc.

## Boot: the dual init guard

`Discord` fires **both** `init` and `application-init`; the command tree needs the
application to be ready. Guard so your setup runs exactly once, after both:

```php
$ready = $appReady = false;
$boot = function () use (&$ready, &$appReady, $bot, ...) {
    if (! $ready || ! $appReady) return;
    $bot->application->commands->freshen()->then($registerCommands);
    $bot->listenCommand('foo', fn (Interaction $i) => $commands->dispatch($i));
    // component routing, presence, ...
};
$bot->once('init', function () use (&$ready, $boot) { $ready = true; $boot(); });
$bot->once('application-init', function () use (&$appReady, $boot) { $appReady = true; $boot(); });
```

Common client options: `'intents' => Intents::getDefaultIntents()`,
`'disableVoiceClient' => true` (unless you need voice — it still opens the gateway
regardless), `'storeMessages' => true` only if you read from the message cache.

## Persistence: a tiny JSON state store

For per-user wallets / identities / settings, a flat JSON file is enough (see
`NHA\StateStore`, `Sabacc\Economy\Store`):

- Load lazily on first access; keep everything in memory.
- **Atomic writes**: `file_put_contents($path.'.'.getmypid().'.tmp', json_encode(...))`
  then `@rename($tmp, $path)`. Sweep stale `*.tmp` on load.
- gitignore the state file: `/var/*` + `!/var/.gitkeep`.
- Do **not** persist live match/session state across restarts unless you need to —
  keep it in memory keyed by message id.

## composer.json

```jsonc
"require": {
    "php": "^8.3",
    "team-reflex/discord-php": "dev-master",
    "discord-php/http": "dev-master as 10.1.7"   // needed: team-reflex/discord-php
},                                               // dev-master requires http ^10.1.7,
"minimum-stability": "dev", "prefer-stable": true,
"repositories": [                                 // local dev against sibling checkouts
    { "type": "path", "url": "../DiscordPHP",      "options": { "symlink": true } },
    { "type": "path", "url": "../DiscordPHP-Http", "options": { "symlink": true } }
]
```

Run `COMPOSER_ROOT_VERSION=dev-main composer install` (Composer cannot detect the
root version through the path repos otherwise). `composer.lock` is gitignored in
these repos.

## Style

`.php-cs-fixer.dist.php` uses `['@auto' => true, 'phpdoc_align' => [...vertical...], 'phpdoc_indent' => true]`.
`pint.json` is `{"preset": "psr12", "rules": {"declare_strict_types": true}}`.
Every file starts with `declare(strict_types=1);` and the project header comment.

## The CRLF / diff gotcha

These repos have `core.autocrlf=true` and no `.gitattributes`, so php-cs-fixer
writes CRLF on disk and Git normalises to LF on `git add`. A raw `git diff` after
running `composer cs` shows **every line changed**. Always inspect the real change
set with `git diff --cached` (after `git add`) or `git diff --cached --stat`, not
`git diff`.

## Part conventions (additive PHPDoc)

In the core `DiscordPHP` repo, PHPDoc changes are **additive only** — add
`@property` / `@param` tags, never reword existing prose (it mirrors Discord's
official docs). Extension repos are freer, but still lead with a one-paragraph
class docblock saying what the class is and what it is NOT responsible for.

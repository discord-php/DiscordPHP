---
name: discord-php-bot-security
description: Audit checklist for DiscordPHP bots and API libraries — stop the bot token leaking to third-party APIs or logs, keep secrets out of custom_ids and exception messages, use constant-time comparison and crypto-random for auth/CSRF/webhook flows, and don't log OAuth codes / full headers / PII. Use when reviewing an HTTP client, an error handler, an OAuth or webhook endpoint, or before publishing a repo.
---

# DiscordPHP Bot Security Skill

A concrete checklist built from real findings across `DiscordPHP-MTG`,
`DiscordPHP-NHA`, `DiscordPHP-Voice` and `Civilizationbot`.

## 1. Never send the Discord bot token to a third party

The classic bug: an extension's `Http` client for `api.example.com` is
constructed with `'Bot '.$this->token` and adds `Authorization: <token>` to
**every** request — so the Discord bot token is sent to an unrelated host on
every call.

- Extension HTTP clients for a third-party API should take an **empty** token and
  only set `Authorization` when a real value is present:
  ```php
  $h = ['User-Agent' => $this->getUserAgent()];
  if ($this->token !== '')  $h['Authorization'] = $this->token;
  if ($this->apiKey !== null) $h['X-Api-Key'] = $this->apiKey;   // that API's own key
  ```
- Drop Discord-only headers (`X-Ratelimit-Precision`) from non-Discord clients.
- Override `getUserAgent()` to identify your library, not `DiscordPHP-HTTP`.

## 2. Keep tokens out of logs and exception messages

- Do not log full request/response bodies on error. A FastAPI-style **422** body
  echoes the request back under `input` — for an auth'd POST that includes the
  token. Redact before logging *and* before putting it in an exception message:
  ```php
  $body = preg_replace('/("(?:nha_)?token"\s*:\s*)"[^"]*"/i', '$1"***"', $body) ?? $body;
  ```
- `Manager`/gateway code: log `['token' => '*****']`, and give payload parts a
  `__debugInfo()` that redacts the token (see `VoicePayload::__debugInfo()`).
  Log identify with `['op' => $payload->op]`, not the whole payload.
- Global error handlers that DM a technician: send `file:line:function` from
  `debug_backtrace()` (no `['args']`). `getTraceAsString()` still inlines scalar
  args truncated to 15 chars — a token *prefix* can leak. Prefer the arg-free
  form.

## 3. Keep secrets out of `custom_id`

Component `custom_id`s are sent to Discord and visible in the client. Capture the
agent/session token in the **server-side listener closure**, never in the id or a
button label. (NHA's `AgentObservation::toContainer($nha, $token)` does this
right — `$token` lives only in the `$submit` closure.)

## 4. OAuth2 / CSRF / webhooks

- **State / session ids**: `bin2hex(random_bytes(16))`, never `uniqid()`
  (predictable microtime).
- **Compare with `hash_equals()`**, not `===` / `!==` — HMAC signatures and CSRF
  state tokens. Reject empty/absent values up front; make the state single-use
  (delete it after a successful exchange).
- **Webhook signature**: verify with `hash_equals()`, prefer
  `X-Hub-Signature-256`, and **fail closed** when the shared secret env var is
  unset (an unset secret makes `hash_hmac` key `''` and every request "valid").
- **Redirect URIs**: always validate the effective `redirect_uri` against an
  allow-list — a caller-supplied value must not bypass the check (open redirect /
  auth-code interception).
- **Don't log** the OAuth authorization `code` (an exchangeable credential), the
  full request header set on a rejected request (a mistyped `Authorization` lands
  in the log), or contact-form email/message bodies (PII — send them to the
  private channel, not the log).
- Raw `curl` in an OAuth path: set `CURLOPT_SSL_VERIFYPEER => true`,
  `CURLOPT_SSL_VERIFYHOST => 2`, `CURLOPT_CONNECTTIMEOUT`, `CURLOPT_TIMEOUT`,
  `curl_close()`, and null-check the result.

## 5. Repo hygiene

- `.gitignore` must cover `.env`, `.env.*`, `composer.lock` (in these repos),
  `/var/*` (state files with tokens), and any bespoke secret file
  (`token.php`, `/json`, `botlog.txt`).
- Verify nothing sensitive is tracked: `git ls-files | grep -iE '\.env|token|secret|\.pem|\.key|credential'`.
- Never place personal data or tokens in URL query strings (they get logged by
  proxies and land in `Referer`).

## Quick grep sweep

```
rg -n "addQuery\(\s*['\"](token|key|secret|password)" src/          # token in URL
rg -n "(debug|info|warn|error).*(token|Authorization|getBody|payload|->data\b)" src/
rg -n "!==|===" src/ | rg -i "hash_hmac|signature|hash_equals"      # non-constant-time
rg -n "uniqid\(" src/                                               # weak randomness
```

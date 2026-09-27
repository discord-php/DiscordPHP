---
title: "Webhook Events"
---

Some events never reach the gateway. Discord sends them as signed HTTP requests to the **Webhook Events URL** set on your application's [Webhooks page](https://discord.com/developers/applications). Lobby messages, game direct messages and application authorizations only arrive this way.

The client has a receiver for them, created the first time you ask for it. It checks each request's signature against your application's verify key, answers Discord within its 3-second limit, and then emits the event on the client like a gateway event:

```php
$discord->on('init', function (Discord $discord) {
    $discord->getWebhookEvents()->listen('127.0.0.1:8080');
});
```

- Discord needs a public HTTPS URL, so put a reverse proxy in front of the address, and point the Webhook Events URL at the proxy's hostname.
- Checking signatures needs PHP's sodium extension. PHP ships with it, including on Windows; enable `extension=sodium` in `php.ini` if `php -m` does not list it.
- The receiver is also a ReactPHP request handler, so if your application already runs a `React\Http\HttpServer`, you can route requests to `$discord->getWebhookEvents()` instead of calling `listen()`.
- Requests that arrive before the client has loaded your application are answered with `503`, so Discord retries them.

Discord retries an event it did not get an answer for, for up to 10 minutes. A retry of an event already delivered is dropped.

### Application Authorized

Called with an `ApplicationAuthorized` object when a user adds your application to a server or to their account.

```php
$discord->on(Event::APPLICATION_AUTHORIZED, function (ApplicationAuthorized $authorized, Discord $discord) {
    // $authorized->user, $authorized->scopes, and $authorized->guild when it was added to a server
});
```

### Application Deauthorized

Called with a `User` object when a user removes your application.

For Social SDK games this is the only out-of-game sign that the user's tokens were revoked and their account unmerged, so forget their session here.

```php
$discord->on(Event::APPLICATION_DEAUTHORIZED, function (User $user, Discord $discord) {
    // ...
});
```

### Lobby Message Create

Called with a `Lobby\Message` object when a message is sent in a lobby.

```php
$discord->on(Event::LOBBY_MESSAGE_CREATE, function (Lobby\Message $message, Discord $discord) {
    // Tell the players' clients how to show it
    $message->updateModerationMetadata(['action' => 'show']);
});
```

### Lobby Message Update

Called with a `Lobby\Message` object when a lobby message is edited. Editing clears its moderation metadata, so moderate it again here. Lobby messages are not cached, so there is no old message.

```php
$discord->on(Event::LOBBY_MESSAGE_UPDATE, function (Lobby\Message $message, Discord $discord) {
    // ...
});
```

### Lobby Message Delete

Called with a `Lobby\Message` object holding only the `id` and `lobby_id` of the deleted message.

```php
$discord->on(Event::LOBBY_MESSAGE_DELETE, function (Lobby\Message $message, Discord $discord) {
    // ...
});
```

### Game Direct Message Create

Called with a `GameDirectMessage` object when a direct message is sent while at least one of its users is in a Social SDK session.

```php
$discord->on(Event::GAME_DIRECT_MESSAGE_CREATE, function (GameDirectMessage $message, Discord $discord) {
    // Tell the players' clients how to show it
    $message->updateModerationMetadata(['action' => 'hide', 'reason' => 'toxicity']);
});
```

### Game Direct Message Update

Called with a `GameDirectMessage` object when a game direct message is edited. Editing clears its moderation metadata, so moderate it again here.

```php
$discord->on(Event::GAME_DIRECT_MESSAGE_UPDATE, function (GameDirectMessage $message, Discord $discord) {
    // ...
});
```

### Game Direct Message Delete

Called with a `GameDirectMessage` object for the deleted message.

```php
$discord->on(Event::GAME_DIRECT_MESSAGE_DELETE, function (GameDirectMessage $message, Discord $discord) {
    // ...
});
```

### Entitlements

`ENTITLEMENT_CREATE`, `ENTITLEMENT_UPDATE` and `ENTITLEMENT_DELETE` arrive over both the gateway and webhooks, and are emitted as usual. When the gateway delivers one first, the webhook copy is dropped.

===============================
OAuth2 sessions and game linking
===============================

DiscordPHP runs in a long-lived CLI process. ``$discord->sessions`` manages
player OAuth2 tokens separately from the bot's HTTP client. Native Social SDK
clients still own player presence, relationships, game invites and audio.
See Discord's `Social SDK overview <https://docs.discord.com/developers/discord-social-sdk/overview>`_.

Linking a Discord account
========================

For a confidential backend, configure the ``clientSecret`` option when creating
the Discord client and wait until the application's ID is known. Forward an
approved authorization callback to that process only after your application
has checked the callback's ``state`` against the authenticated player's saved
state and handled any ``error`` response. Use the exact registered redirect URI.
DiscordPHP does not own your browser session or validate callback state.

.. code:: php

   use Discord\OAuth2\Session;
   use Discord\Parts\User\User;

   // $code, $redirectUri and $playerId come from your validated linking flow.
   $discord->sessions->exchangeAuthorizationCode($code, $redirectUri, $playerId)
       ->then(function (Session $session) {
           return $session->getCurrentUser();
       })
       ->then(function (User $user) {
           echo 'Linked Discord user: '.$user->id.PHP_EOL;
           // Persist the mapping from your authenticated player to this Discord ID.
       });

This exchanges the code using application credentials and form encoding, then
hydrates a ``Session`` and stores its token under the optional key. Omit the
key for a session that is not stored. The default ``ArrayTokenStore`` lasts
only until the process exits; configure a durable ``tokenStore`` for persistent
links. Keep tokens and the client secret on the backend.

Request the scopes needed by your integration. ``identify`` permits
``getCurrentUser()``; game stats writes require ``application_identities.write``.
Native SDK presence uses ``openid sdk.social_layer_presence``; communication
uses ``openid sdk.social_layer``. This exchange helper is for confidential
clients with a secret; public-client PKCE authorization remains a separate flow.
See the `web linking guide <https://docs.discord.com/developers/discord-social-sdk/development-guides/account-linking-on-web>`_
and `SDK scopes <https://docs.discord.com/developers/discord-social-sdk/core-concepts/oauth2-scopes>`_.

Unlinking and revocation
=======================

.. code:: php

   // $session is the linked player's Session.
   $discord->sessions->revoke($session)->then(function (bool $deleted) {
       if (! $deleted) {
           throw new RuntimeException('Discord revoked the grant, but local token cleanup failed.');
       }
       // Clear your account mapping and other token-store keys for the player.
   });

``revoke()`` calls Discord first, using the application's credentials. On
success it forgets this session's key in memory and deletes its stored token;
unkeyed sessions resolve to ``true``. A rejected Discord request leaves local
state available for retry. A token-store rejection propagates; a ``false``
deletion result reports incomplete local cleanup after remote revocation.
Stop using retained Session objects after revocation.

Discord invalidates the authorization's access and refresh tokens together.
Only the supplied session's local key is removed, because your application
owns the mapping between Discord users and arbitrary storage keys. Remove any
other keys and account records for that player yourself. ``forget($key)`` only
deletes local state; it does not revoke authorization at Discord.
See `token revocation <https://docs.discord.com/developers/topics/oauth2#token-revocation-example>`_
and `unlinking accounts <https://docs.discord.com/developers/discord-social-sdk/development-guides/unlinking-accounts>`_.

Out-of-band deauthorization
==========================

Users may revoke your application from Discord. Subscribe to
``APPLICATION_DEAUTHORIZED`` in the application's Webhook Events settings and
configure ``$discord->getWebhookEvents()`` as a signed webhook receiver. The
event's handler emits a ``User``; use its ``id`` to look up your player mapping,
call ``forget()`` for all of that player's stored keys, and clear the mapping.
The library cannot infer your storage keys from the Discord user ID.
These notifications arrive through `Webhook Events <https://docs.discord.com/developers/events/webhook-events>`_,
separately from Gateway delivery.

Provisional accounts
====================

``createProvisionalAccount()`` and ``exchangeExternalToken()`` already provide
backend issuance. Reacquire provisional tokens with the original issuance
method; ordinary OAuth refresh is for linked-account grants. In particular,
Discord deprecates the OIDC provisional refresh-token grant. Unlinking a
provisional account uses ``unmergeProvisionalAccount()`` or
``unmergeExternalAccount()``. See `managing provisional accounts <https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/managing-accounts>`_.

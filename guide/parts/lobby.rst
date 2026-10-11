=====
Lobby
=====

Lobbies contain a typed collection of ``Discord\Parts\Lobby\Member`` objects in
``$lobby->members``. The bot's ``$discord->lobbies`` repository manages membership
through the `Lobby HTTP API <https://docs.discord.com/developers/resources/lobby>`_.

Updating the roster
===================

.. code:: php

   $lobby->addMember($userId, ['additional_name' => 'Sir Lancelot'])
       ->then(function (\Discord\Parts\Lobby\Member $member) use ($lobby) {
           // The lobby roster now includes the member returned by Discord.
           echo $lobby->members->get('id', $member->id)->additional_name;
       });

``addMember()`` also updates an existing member. ``removeMember()`` removes a
member from the roster after Discord accepts the request. These methods update
both the supplied lobby object and the bot repository's cached lobby, when present.
Their promises wait for asynchronous cache reads and writes. Failed HTTP requests
leave the roster unchanged.

``bulkUpdateMembers()`` accepts arrays or member parts and returns a typed
collection of the members Discord added or updated:

.. code:: php

   $lobby->bulkUpdateMembers([
       ['id' => $userId, 'metadata' => ['team' => 'red']],
       ['id' => $departingUserId, 'remove_member' => true],
   ])->then(function ($upserted) {
       // Removed users are not included in this collection.
   });

Only returned upserts are added to the roster; users silently omitted by Discord
are not added or changed locally. Successful requested removals are applied, and
unrelated members are preserved. Omitting ``additional_name`` preserves its remote
value, while sending ``null`` clears it. Optional fields, including nullable
``metadata``, are forwarded as supplied.

When called with an uncached lobby id, roster operations do not fetch the lobby or
create a cache entry containing only the changed members. Fetch a complete lobby
first when the application needs its roster.

Player sessions
===============

Player operations use ``$session->lobbies`` and the player's OAuth2 token. Each
session repository has its own cache namespace, including when the bot and several
players share one cache backend. Its cache is scoped to the repository instance;
a new session instance does not restore the previous instance's lobby cache.
Bot roster changes do not update other player repositories, and leaving a lobby
through one session does not evict it from another session or the bot repository.

For the distinction between server and client lobby management, see
`Discord's lobby guide <https://docs.discord.com/developers/discord-social-sdk/development-guides/managing-lobbies>`_.

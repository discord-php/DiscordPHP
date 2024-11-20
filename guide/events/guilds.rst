======
Guilds
======


Requires the ``Intents::GUILDS`` intent.

Guild Create
============

Called with a ``Guild`` object in one of the following situations:

1. When the Bot is first starting and the guilds are becoming available. (unless the listener is put inside after ‘init’ event)
2. When a guild was unavailable and is now available due to an outage.
3. When the Bot joins a new guild.

.. code:: php

   $discord->on(Event::GUILD_CREATE, function (object $guild, Discord $discord) {
       if (! ($guild instanceof Guild)) {
           // the guild is unavailable due to an outage, $guild is a stdClass
           // {
           //     "id": "",
           //     "unavailable": true,
           // }
           return;
       }

       // the Bot has joined the guild
   });

Guild Update
============

Called with two ``Guild`` objects when a guild is updated.

.. code:: php

   $discord->on(Event::GUILD_UPDATE, function (Guild $guild, Discord $discord, ?Guild $oldGuild) {
       // ...
   });

Guild Delete
============

Called with a ``Guild`` object in one of the following situations:

1. The Bot was removed from a guild.
2. The guild is unavailable due to an outage.

.. code:: php

   $discord->on(Event::GUILD_DELETE, function (object $guild, Discord $discord, bool $unavailable) {
       // ...
       if ($unavailable) {
           // the guild is unavailabe due to an outage, $guild is a stdClass
           // {
           //     "guild_id": "",
           //     "unavailable": "",
           // }
       } else {
           // the Bot has been kicked from the guild
       }
   });

Guild Bans
==========

Requires the ``Intents::GUILD_BANS`` intent and ``ban_members`` permission.

Guild Ban Add
-------------

Called with a ``Ban`` object when a member is banned from a guild.

.. code:: php

   $discord->on(Event::GUILD_BAN_ADD, function (Ban $ban, Discord $discord) {
       // ...
   });

Guild Ban Remove
----------------

Called with a ``Ban`` object when a user is unbanned from a guild.

.. code:: php

   $discord->on(Event::GUILD_BAN_REMOVE, function (Ban $ban, Discord $discord) {
       // ...
   });

Guild Emojis and Stickers
=========================

Requires the ``Intents::GUILD_EMOJIS_AND_STICKERS`` intent.

Guild Emojis Update
-------------------

Called with two Collections of ``Emoji`` objects when a guild’s emojis have been added/updated/deleted. ``$oldEmojis`` *may* be empty if it was not cached or there were previously no emojis.

.. code:: php

   $discord->on(Event::GUILD_EMOJIS_UPDATE, function (Collection $emojis, Discord $discord, Collection $oldEmojis) {
       // ...
   });

Guild Stickers Update
---------------------

Called with two Collections of ``Sticker`` objects when a guild’s stickers have been added/updated/deleted. ``$oldStickers`` *may* be empty if it was not cached or there were previously no stickers.

.. code:: php

   $discord->on(Event::GUILD_STICKERS_UPDATE, function (Collection $stickers, Discord $discord, Collection $oldStickers) {
       // ...
   });

Guild Members
=============

Requires the ``Intents::GUILD_MEMBERS`` intent. This intent is a priviliged intent, it must be enabled in your Discord Bot developer settings.

Guild Member Add
----------------

Called with a ``Member`` object when a new user joins a guild.

.. code:: php

   $discord->on(Event::GUILD_MEMBER_ADD, function (Member $member, Discord $discord) {
       // ...
   });

Guild Member Remove
-------------------

Called with a ``Member`` object when a member is removed from a guild (leave/kick/ban). Note that the member *may* only have ``User`` data if ``loadAllMembers`` is disabled.

.. code:: php

   $discord->on(Event::GUILD_MEMBER_REMOVE, function (Member $member, Discord $discord) {
       // ...
   });

Guild Member Update
-------------------

Called with two ``Member`` objects when a member is updated in a guild. Note that the old member *may* be ``null`` if ``loadAllMembers`` is disabled.

.. code:: php

   $discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $member, Discord $discord, ?Member $oldMember) {
       // ...
   });

Guild Roles
===========

Requires the ``Intents::GUILDS`` intent.

Guild Role Create
-----------------

Called with a ``Role`` object when a role is created in a guild.

.. code:: php

   $discord->on(Event::GUILD_ROLE_CREATE, function (Role $role, Discord $discord) {
       // ...
   });

Guild Role Update
-----------------

Called with two ``Role`` objects when a role is updated in a guild.

.. code:: php

   $discord->on(Event::GUILD_ROLE_UPDATE, function (Role $role, Discord $discord, ?Role $oldRole) {
       // ...
   });

Guild Role Delete
-----------------

Called with a ``Role`` object when a role is deleted in a guild. ``$role`` may return ``Role`` object if it was cached.

.. code:: php

   $discord->on(Event::GUILD_ROLE_DELETE, function (object $role, Discord $discord) {
       if ($role instanceof Role) {
           // $role was cached
       }
       // $role was not in cache:
       else {
           // {
           //     "guild_id": "" // role guild ID
           //     "role_id": "", // role ID,
           // }
       }
   });

Guild Scheduled Events
======================

Requires the ``Intents::GUILD_SCHEDULED_EVENTS`` intent.

Guild Scheduled Event Create
----------------------------

Called with a ``ScheduledEvent`` object when a scheduled event is created in a guild.

.. code:: php

   $discord->on(Event::GUILD_SCHEDULED_EVENT_CREATE, function (ScheduledEvent $scheduledEvent, Discord $discord) {
       // ...
   });

Guild Scheduled Event Update
----------------------------

Called with a ``ScheduledEvent`` object when a scheduled event is updated in a guild.

.. code:: php

   $discord->on(Event::GUILD_SCHEDULED_EVENT_UPDATE, function (ScheduledEvent $scheduledEvent, Discord $discord, ?ScheduledEvent $oldScheduledEvent) {
       // ...
   });

Guild Scheduled Event Delete
----------------------------

Called with a ``ScheduledEvent`` object when a scheduled event is deleted in a guild.

.. code:: php

   $discord->on(Event::GUILD_SCHEDULED_EVENT_DELETE, function (ScheduledEvent $scheduledEvent, Discord $discord) {
       // ...
   });

Guild Scheduled Event User Add
------------------------------

Called when a user has subscribed to a scheduled event in a guild.

.. code:: php

   $discord->on(Event::GUILD_SCHEDULED_EVENT_USER_ADD, function ($data, Discord $discord) {
       // ...
   });

Guild Scheduled Event User Remove
---------------------------------

Called when a user has unsubscribed from a scheduled event in a guild.

.. code:: php

   $discord->on(Event::GUILD_SCHEDULED_EVENT_USER_REMOVE, function ($data, Discord $discord) {
       // ...
   });

Integrations
============

Requires the ``Intents::GUILD_INTEGRATIONS`` intent.

Guild Integrations Update
-------------------------

Called with a cached ``Guild`` object when a guild integration is updated.

.. code:: php

   $discord->on(Event::GUILD_INTEGRATIONS_UPDATE, function (object $guild, Discord $discord) {
       if ($guild instanceof Guild) {
           // $guild was cached
       }
       // $guild was not in cache:
       else {
           // {
           //     "guild_id": "",
           // }
       }
   });

Integration Create
------------------

Called with an ``Integration`` object when an integration is created in a guild.

.. code:: php

   $discord->on(Event::INTEGRATION_CREATE, function (Integration $integration, Discord $discord) {
       // ...
   });

Integration Update
------------------

Called with an ``Integration`` object when a integration is updated in a guild.

.. code:: php

   $discord->on(Event::INTEGRATION_UPDATE, function (Integration $integration, Discord $discord, ?Integration $oldIntegration) {
       // ...
   });

Integration Delete
------------------

Called with an old ``Integration`` object when a integration is deleted from a guild.

.. code:: php

   $discord->on(Event::INTEGRATION_DELETE, function (object $integration, Discord $discord) {
       if ($integration instanceof Integration) {
           // $integration was cached
       }
       // $integration was not in cache:
       else {
           // {
           //     "id": "",
           //     "guild_id": "",
           //     "application_id": ""
           // }
       }
   });


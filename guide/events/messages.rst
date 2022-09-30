========
Messages
========


   Unlike persistent messages, ephemeral messages are sent directly to the user and the Bot who sent the message rather than through the guild channel. Because of this, ephemeral messages are tied to the ``Intents::DIRECT_MESSAGES``, and the message object won’t include ``guild_id`` or ``member``.

Requires the ``Intents::GUILD_MESSAGES`` intent for guild or ``Intents::DIRECT_MESSAGES`` for direct messages.

Message Create
==============

Called with a ``Message`` object when a message is sent in a guild or private channel.

.. code:: php

   $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
       // ...
   });

Message Update
==============

Called with two ``Message`` objects when a message is updated in a guild or private channel. The old message may be null if ``storeMessages`` is not enabled *or* the message was sent before the Bot was started. Discord does not provide a way to get message update history.

.. code:: php

   $discord->on(Event::MESSAGE_UPDATE, function (Message $message, Discord $discord, ?Message $oldMessage) {
       // ...
   });

Message Delete
==============

Called with an old ``Message`` object *or* the raw payload when a message is deleted. The ``Message`` object may be the raw payload if ``storeMessages`` is not enabled *or* the message was sent before the Bot was started. Discord does not provide a way to get deleted messages.

.. code:: php

   $discord->on(Event::MESSAGE_DELETE, function (object $message, Discord $discord) {
       if ($message instanceof Message) {
           // Message is present in cache
       }
       // If the message is not present in the cache:
       else {
           // {
           //     "id": "", // deleted message ID,
           //     "channel_id": "", // message channel ID,
           //     "guild_id": "" // channel guild ID
           // }
       }
   });

Message Delete Bulk
===================

Called with a ``Collection`` of old ``Message`` objects *or* the raw payload when bulk messages are deleted. The ``Message`` object may be the raw payload if ``storeMessages`` is not enabled *or* the message was sent before the Bot was started. Discord does not provide a way to get deleted messages.

.. code:: php

   $discord->on(Event::MESSAGE_DELETE_BULK, function (Collection $messages, Discord $discord) {
       foreach ($messages as $message) {
           if ($message instanceof Message) {
               // Message is present in cache
           }
           // If the message is not present in the cache:
           else {
               // {
               //     "id": "", // deleted message ID,
               //     "channel_id": "", // message channel ID,
               //     "guild_id": "" // channel guild ID
               // }
           }
       }
   });

Message Reactions
=================

Requires the ``Intents::GUILD_MESSAGE_REACTIONS`` intent for guild or ``Intents::DIRECT_MESSAGE_REACTIONS`` for direct messages.

Message Reaction Add
--------------------

Called with a ``MessageReaction`` object when a user added a reaction to a message.

.. code:: php

   $discord->on(Event::MESSAGE_REACTION_ADD, function (MessageReaction $reaction, Discord $discord) {
       // ...
   });

Message Reaction Remove
-----------------------

Called with a ``MessageReaction`` object when a user removes a reaction from a message.

.. code:: php

   $discord->on(Event::MESSAGE_REACTION_REMOVE, function (MessageReaction $reaction, Discord $discord) {
       // ...
   });

Message Reaction Remove All
---------------------------

Called with a ``MessageReaction`` object when all reactions are removed from a message. Note that only the fields relating to the message, channel and guild will be filled.

.. code:: php

   $discord->on(Event::MESSAGE_REACTION_REMOVE_ALL, function (MessageReaction $reaction, Discord $discord) {
       // ...
   });

Message Reaction Remove Emoji
-----------------------------

Called with an object when all reactions of an emoji are removed from a message. Unlike Message Reaction Remove, this event contains no users or members.

.. code:: php

   $discord->on(Event::MESSAGE_REACTION_REMOVE_EMOJI, function (MessageReaction $reaction, Discord $discord) {
       // ...
   });


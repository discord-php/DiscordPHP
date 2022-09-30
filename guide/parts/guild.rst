=====
Guild
=====


Guilds represent Discord ‘servers’.

Repositories
============

+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| name                   | type                                  | notes                                                                            |
+========================+=======================================+==================================================================================+
| roles                  | `Role <#role>`_                      |                                                                                  |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| emojis                 | `Emoji <#emoji>`_                    |                                                                                  |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| members                | `Member <#member>`_                  | May not contain offline members, see the ```loadAllMembers`` option <#basics>`_ |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| channels               | `Channel <#channel>`_                |                                                                                  |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| stage_instances        | `StageInstance <#stage_instance>`_   |                                                                                  |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| guild_scheduled_events | `ScheduledEvent <#scheduled_event>`_ |                                                                                  |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| stickers               | `Sticker <#sticker>`_                |                                                                                  |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| invites                | `Invite <#invite>`_                  | Not initially loaded                                                             |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| bans                   | `Ban <#ban>`_                        | Not initially loaded without ```retrieveBans`` option <#basics>`_               |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| commands               | `Command <#command>`_                | Not initially loaded                                                             |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| templates              | `GuildTemplate <#guild_template>`_   | Not initially loaded                                                             |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+
| integrations           | `Integration <#integration>`_        | Not initially loaded                                                             |
+------------------------+---------------------------------------+----------------------------------------------------------------------------------+

Creating a role
===============

Shortcut for ``$guild->roles->save($role);``. Takes an array of parameters for a role and returns a role part in a promise.

Parameters
----------

+---------------+---------+------------------------------+-----------------------+
| name          | type    | description                  | default               |
+===============+=========+==============================+=======================+
| name          | string  | Role name                    | new role              |
+---------------+---------+------------------------------+-----------------------+
| permissions   | string  | Bitwise value of permissions | @everyone permissions |
+---------------+---------+------------------------------+-----------------------+
| color         | integer | RGB color value              | 0                     |
+---------------+---------+------------------------------+-----------------------+
| hoist         | bool    | Hoisted role?                | false                 |
+---------------+---------+------------------------------+-----------------------+
| icon          | string  | image data for Role icon     | null                  |
+---------------+---------+------------------------------+-----------------------+
| unicode_emoji | string  | unicode emoji for Role icon  | null                  |
+---------------+---------+------------------------------+-----------------------+
| mentionable   | bool    | Mentionable role?            | false                 |
+---------------+---------+------------------------------+-----------------------+

.. code:: php

   $guild->createRole([
       'name' => 'New Role',
       // ...
   ])->done(function (Role $role) {
       // ...
   });

Transferring ownership of guild
===============================

Transfers the ownership of the guild to another member. The bot must own the guild to be able to transfer ownership. Takes a member object or a member ID and returns nothing in a promise.

.. _parameters-1:

Parameters
----------

====== =================== ===========================
name   type                description
====== =================== ===========================
member Member or member ID The member to get ownership
reason string              Reason for Audit Log
====== =================== ===========================

.. code:: php

   $guild->transferOwnership($member)->done(...);
   // or
   $guild->transferOwnership('member_id')->done(...);

Unbanning a member with a User or user ID
=========================================

Unbans a member when passed a ``User`` object or a user ID. If you have the ban object, you can do ``$guild->bans->delete($ban);``. Returns nothing in a promise.

.. _parameters-2:

Parameters
----------

======= =================== =================
name    type                description
======= =================== =================
user_id ``User`` or user ID The user to unban
======= =================== =================

.. code:: php

   $guild->unban($user)->done(...);
   // or
   $guild->unban('user_id')->done(...);

Querying the Guild audit log
============================

Takes an array of parameters to query the audit log for the guild. Returns an Audit Log object inside a promise.

.. _parameters-3:

Parameters
----------

+-------------+-----------------------------------+--------------------------------------------------------+
| name        | type                              | description                                            |
+=============+===================================+========================================================+
| user_id     | string, int, ``Member``, ``User`` | Filters audit log by who performed the action          |
+-------------+-----------------------------------+--------------------------------------------------------+
| action_type | ``Entry`` constants               | Filters audit log by the type of action                |
+-------------+-----------------------------------+--------------------------------------------------------+
| before      | string, int, ``Entry``            | Retrieves audit logs before the given audit log object |
+-------------+-----------------------------------+--------------------------------------------------------+
| limit       | int between 1 and 100             | Limits the amount of audit log entries to return       |
+-------------+-----------------------------------+--------------------------------------------------------+

.. code:: php

   $guild->getAuditLog([
       'user_id' => '123456',
       'action_type' => Entry::CHANNEL_CREATE,
       'before' => $anotherEntry,
       'limit' => 12,
   ])->done(function (AuditLog $auditLog) {
       foreach ($auditLog->audit_log_entries as $entry) {
           // $entry->...
       }
   });

Creating an Emoji
=================

Takes an array of parameters for an emoji and returns an emoji part in a promise. Use the second parameter to specify local file path instead.

.. _parameters-4:

Parameters
----------

+-------+--------+------------------------------------------------------------------+------------+
| name  | type   | description                                                      | default    |
+=======+========+==================================================================+============+
| name  | string | Emoji name                                                       | *required* |
+-------+--------+------------------------------------------------------------------+------------+
| image | string | image data with base64 format, ignored if file path is specified |            |
+-------+--------+------------------------------------------------------------------+------------+
| roles | array  | Role IDs that are allowed to use the emoji                       | []         |
+-------+--------+------------------------------------------------------------------+------------+

.. code:: php

   $guild->createEmoji([
       'name' => 'elephpant',
       // ...
   ],
   '/path/to/file.jpg',
   'audit-log reason'
   )->done(function (Emoji $emoji) {
       // ...
   });
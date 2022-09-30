===========
Permissions
===========


There are two types of permissions - channel permissions and role permissions. They are represented by their individual classes, but both extend the same abstract permission class.

Properties
==========

===================== ==== ======================
name                  type description
===================== ==== ======================
bitwise               int  bitwise representation
create_instant_invite bool 
manage_channels       bool 
view_channel          bool 
manage_roles          bool 
===================== ==== ======================

The rest of the properties are listed under each permission type, all are type of ``bool``.

Methods
=======

Get all valid permissions
-------------------------

Returns a list of valid permissions, in key value form. Static method.

.. code:: php

   var_dump(ChannelPermission::getPermissions());
   // [
   //     'priority_speaker' => 8,
   //     // ...
   // ]

Channel Permission
==================

Represents permissions for text, voice, and stage instance channels.

Text Channel Permissions
------------------------

-  ``create_instant_invite``
-  ``manage_channels``
-  ``view_channel``
-  ``manage_roles``
-  ``add_reactions``
-  ``send_messages``
-  ``send_tts_messages``
-  ``manage_messages``
-  ``embed_links``
-  ``attach_files``
-  ``read_message_history``
-  ``mention_everyone``
-  ``use_external_emojis``
-  ``manage_webhooks``
-  ``use_application_commands``
-  ``manage_threads``
-  ``create_public_threads``
-  ``create_private_threads``
-  ``use_external_stickers``
-  ``send_messages_in_threads``

Voice Channel Permissions
-------------------------

-  ``create_instant_invite``
-  ``manage_channels``
-  ``view_channel``
-  ``manage_roles``
-  ``priority_speaker``
-  ``stream``
-  ``connect``
-  ``speak``
-  ``mute_members``
-  ``deafen_members``
-  ``move_members``
-  ``use_vad``
-  ``manage_events``
-  ``use_embedded_activities`` was ``start_embedded_activities``

Stage Instance Channel Permissions
----------------------------------

-  ``create_instant_invite``
-  ``manage_channels``
-  ``view_channel``
-  ``manage_roles``
-  ``connect``
-  ``mute_members``
-  ``deafen_members``
-  ``move_members``
-  ``request_to_speak``
-  ``manage_events``

Role Permissions
================

Represents permissions for roles.

Permissions
-----------

-  ``create_instant_invite``
-  ``manage_channels``
-  ``view_channel``
-  ``manage_roles``
-  ``kick_members``
-  ``ban_members``
-  ``administrator``
-  ``manage_guild``
-  ``view_audit_log``
-  ``view_guild_insights``
-  ``change_nickname``
-  ``manage_nicknames``
-  ``manage_emojis_and_stickers``
-  ``moderate_members``
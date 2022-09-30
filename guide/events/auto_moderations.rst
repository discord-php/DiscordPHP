================
Auto Moderations
================


All auto moderation related events are currently only sent to bot users which have the ``MANAGE_GUILD`` permission.

Auto Moderation Rule Create
===========================

Called with a ``Rule`` object when an auto moderation rule is created.

.. code:: php

   $discord->on(Event::AUTO_MODERATION_RULE_CREATE, function (Rule $rule, Discord $discord) {
       // ...
   });

Requires the ``Intents::AUTO_MODERATION_CONFIGURATION`` intent.

Auto Moderation Rule Update
===========================

Called with a ``Rule`` object when an auto moderation rule is updated.

.. code:: php

   $discord->on(Event::AUTO_MODERATION_RULE_UPDATE, function (Rule $rule, Discord $discord, ?Rule $oldRule) {
       // ...
   });

Auto Moderation Rule Delete
===========================

Called with a ``Rule`` object when an auto moderation rule is deleted.

.. code:: php

   $discord->on(Event::AUTO_MODERATION_RULE_DELETE, function (Rule $rule, Discord $discord) {
       // ...
   });

Requires the ``Intents::AUTO_MODERATION_CONFIGURATION`` intent.

Auto Moderation Action Execution
================================

Called with an ``AutoModerationActionExecution`` object when an auto moderation rule is triggered and an action is executed (e.g. when a message is blocked).

.. code:: php

   // use `Discord\Parts\WebSockets\AutoModerationActionExecution`;

   $discord->on(Event::AUTO_MODERATION_ACTION_EXECUTION, function (AutoModerationActionExecution $actionExecution, Discord $discord) {
       // ...
   });

Requires the ``Intents::AUTO_MODERATION_EXECUTION`` intent.


===============
Stage Instances
===============


Requires the ``Intents::GUILDS`` intent.

Stage Instance Create
=====================

Called with a ``StageInstance`` object when a stage instance is created (i.e. the Stage is now “live”).

.. code:: php

   $discord->on(Event::STAGE_INSTANCE_CREATE, function (StageInstance $stageInstance, Discord $discord) {
       // ...
   });

Stage Instance Update
=====================

Called with a ``StageInstance`` objects when a stage instance has been updated.

.. code:: php

   $discord->on(Event::STAGE_INSTANCE_UPDATE, function (StageInstance $stageInstance, Discord $discord, ?StageInstance $oldStageInstance) {
       // ...
   });

Stage Instance Delete
=====================

Called with a ``StageInstance`` object when a stage instance has been deleted (i.e. the Stage has been closed).

.. code:: php

   $discord->on(Event::STAGE_INSTANCE_DELETE, function (StageInstance $stageInstance, Discord $discord) {
       // ...
   });


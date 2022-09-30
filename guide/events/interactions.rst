============
Interactions
============


Interaction Create
==================

Called with an ``Interaction`` object when an interaction is created.

.. code:: php

   // use Discord\Parts\Interactions\Interaction;

   $discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
       // ...
   });

Application Command & Message component listeners are processed before this event is called. Useful if you want to create a customized callback or have interaction response persists after Bot restart.


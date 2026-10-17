.. toctree::
   :hidden:

   guild
   channel
   member
   message
   user
   lobby

=====
Parts
=====


Parts is the term used for the data structures inside Discord. All parts share a common set of attributes and methods.

Parts have a set list of fillable fields. If you attempt to set a field that is not accessible, it will not warn you.

To create a part object, you can use the ``new`` syntax or the ``factory`` method. For example, creating a ``Message`` part:

.. code:: php

   $message = new Message($discord);
   // or
   $message = $discord->factory->create(Message::class);

Part attributes can be accessed similar to an object or like an array:

.. code:: php

   $message->content = 'hello!';
   // or
   $message['content'] = 'hello!';

   echo $message->content;
   // or
   echo $message['content'];

``isset()`` and ``empty()`` read an attribute the same way, so they agree with ``??``. An attribute is set when reading it gives a non-null value. That includes computed properties such as ``$message->guild``, which is only set while the guild is cached. Because of this, ``Collection::get()`` can find parts by any attribute, not only the discriminator:

.. code:: php

   isset($message->content); // true once the message has content
   isset($message->guild);   // true when the guild is cached

   $input = $components->get('custom_id', 'instructions');

Computed relations use local caches and do not fetch from Discord. Entitlements
expose ``guild`` and ``user``, subscriptions expose ``user``, and scheduled event
exceptions and message interactions expose ``guild``. These properties return
``null`` when the corresponding ID is absent or the related object is not cached.
They are derived from the IDs and are not added to the raw or serialized payload.

Presence updates expose ``embedded_status`` from ``client_status.embedded`` in the
same way as ``desktop_status``, ``mobile_status``, ``web_status`` and ``vr_status``.
It returns ``null`` when no embedded application session is present.

Filling a part with data
========================

The ``->fill(array $attributes)`` function takes an array of attributes to fill the part. If a field is found that is not ‘fillable’, it is skipped.

.. code:: php

   $message->fill([
       'content' => 'hello!',
   ]);

Getting the raw attributes of a part
====================================

The ``->getRawAttributes()`` function returns the array representation of the part.

.. code:: php

   $attributes = $message->getRawAttributes();
   /**
    * [
    *     "id" => "",
    *     "content" => "",
    *     // ...
    * ]
    */

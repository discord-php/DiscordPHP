======
Basics
======

First step is to include the Composer autoload file and `import <https://www.php.net/manual/en/language.namespaces.importing.php>`_ any required classes.

.. code:: php

   <?php

   use Discord\Discord;
   use Discord\WebSockets\Intents;
   use Discord\WebSockets\Event;

   include __DIR__.'/vendor/autoload.php';

The Discord instance can be set up with an array of options. All are optional except for token:

.. code:: php

   $discord = new Discord([
       'token' => 'Your-Token-Here',
       'intents' => [
           Intents::GUILDS, Intents::GUILD_BANS, // ...
       ],
       // or
       'intents' => 12345,
       // or
       'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS, // default intents as well as guild members
       'loadAllMembers' => false,
       'storeMessages' => false,
       'retrieveBans' => false,
       'pmChannels' => false,
       'disabledEvents' => [
           Event::MESSAGE_CREATE, Event::MESSAGE_DELETE, // ...
       ],
       'loop' => \React\EventLoop\Factory::create(),
       'logger' => new \Monolog\Logger('New logger'),
       'dnsConfig' => '1.1.1.1',
       'shardId' => 0,
       'shardCount' => 5,
   ]);

``token`` is your Discord token. **Required**.

``intents`` can be an array of valid intents *or* an integer representing the intents. Default is all intents minus any privileged intents. At the moment this means all intents minus ``GUILD_MEMBERS`` and ``GUILD_PRESENCES``. To enable these intents you must first enable them in your Discord developer portal.

``loadAllMembers`` is a boolean whether all members should be fetched and stored on bot start. Loading members takes a while to retrieve from Discord and store, so default is false. This requires the ``GUILD_MEMBERS`` intent to be enabled in DiscordPHP. See above for more details.

``storeMessages`` is a boolean whether messages received and sent should be stored. Default is false.

``retrieveBans`` is a boolean whether bans should be retrieved on bot load. Default is false.

``pmChannels`` is a boolean whether PM channels should be stored on bot load. Default is false.

``disabledEvents`` is an array of events that will be disabled. By default all events are enabled.

``loop`` is an instance of a ReactPHP event loop that can be provided to the client rather than creating a new loop. Useful if you want to use other React components. By default, a new loop is created.

``logger`` is an instance of a logger that implements ``LoggerInterface``. By default, a new Monolog logger with log level DEBUG is created to print to stdout.

``dnsConfig`` is an instace of ``Config`` or a string of name server address. By default system setting is used and fall back to 8.8.8.8 when system configuration is not found. Currently only used for VoiceClient.

----

The following options should only be used by large bots that require sharding. If you plan to use sharding, `read up <https://discord.com/developers/docs/topics/gateway#sharding>`_ on how Discord implements it.

``shardId`` is the ID of the bot shard.

``shardCount`` is the number of shards that you are using.

----

Gateway events should be registered inside the ``init`` event, which is emitted once when the bot first starts and has connected to the gateway.

To register an event we use the ``$discord->on(...)`` function, which registers a handler. A list of events is available `here <https://github.com/discord-php/DiscordPHP/blob/master/src/Discord/WebSockets/Event.php#L30-L75>`_. They are described in more detail in further sections of the documentation. All events take a callback which is called when the event is triggered, and the callback is called with an object representing the content of the event and an instance of the ``Discord`` client.

.. code:: php

   $discord->on('init', function (Discord $discord) {

       $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
           // ... handle message sent
       });

   });


Finally, the event loop needs to be started. Treat this as an infinite loop.

.. code:: php

   $discord->run();

If you want to stop the bot you can run:

.. code:: php

   $discord->close();

If you want to stop the bot without stopping the event loop, the close function takes a boolean:

.. code:: php

   $discord->close(false);

Observing the Gateway transport
------------------------------

``isConnected()`` reports an open Gateway WebSocket while the client is not closing.
It does not mean that authentication succeeded, that ``init`` has fired, that guild
or member caches are complete, or that continuity was uninterrupted. Continue using
``init`` for initial startup and ``reconnected`` for recovery. Applications that need
session observation can track READY/RESUMED and INVALID_SESSION/RECONNECT through the
existing ``raw`` event; raw callbacks run before internal handling. Keep generations,
snapshot validation and retry decisions in the application.

Register ``disconnected`` before running the loop, not inside ``init``, to include
loss before initialization:

.. code:: php

   $discord->on('disconnected', function (int $code, string $reason, Discord $discord) {
       // isConnected() is already false. Invalidate application work here.
   });

The event fires synchronously once per connected transport, including normal and
critical close codes. State becomes false before cleanup; notification occurs after
existing timer cleanup and reconnect scheduling. On explicit ``close()``, state becomes
false before requesting socket close, while notification happens after shutdown work
(including ``closed`` and stopping the loop, if requested). A synchronous or delayed
WebSocket close callback does not duplicate the notification. For explicit shutdown,
the code and reason are locally requested, not a server acknowledgement. Failed
connection attempts that never opened a transport do not emit it.

Listeners run inline: keep them short and avoid throwing. A thrown listener exception
propagates, but cannot skip the close cleanup or shutdown work that precedes notification.
This event does not change the client's existing reconnection policy. It describes
transport loss, not every possible session change or the validity of application data.

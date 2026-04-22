Quickstart
==========

This guide takes you from zero to a running Discord bot in under five minutes.

Prerequisites
-------------

- PHP 8.1 or newer (CLI)
- `Composer <https://getcomposer.org>`_
- A Discord bot application and its **Bot Token**

1. Install DiscordPHP
---------------------

.. code-block:: bash

   composer require team-reflex/discord-php

2. Set up your environment
--------------------------

Run ``composer install``. The post-install script automatically copies
``.env.example`` to ``.env`` for you. Open it and fill in your token:

.. code-block:: text

   # .env
   DISCORD_TOKEN=your_bot_token_here

3. Create your bot
------------------

Create a file called ``bot.php`` in your project root:

.. code-block:: php

   <?php

   require_once __DIR__ . '/vendor/autoload.php';

   use Discord\Discord;
   use Discord\Parts\Channel\Message;

   // fromEnv() loads .env automatically; throws a clear error if it's missing
   $discord = Discord::fromEnv();

   $discord->onReady(function (Discord $discord) {
       echo 'Logged in as ' . $discord->user->username . '!' . PHP_EOL;

       $discord->onMessage(function (Message $message) {
           // Ignore bots
           if ($message->author->bot) {
               return;
           }

           if ($message->content === '!ping') {
               $message->reply('Pong!');
           }
       });
   });

   $discord->run();

4. Run the bot
--------------

.. code-block:: bash

   php bot.php

You should see ``Logged in as YourBot#0001!`` in the terminal.

5. Invite your bot to a server
-------------------------------

1. Open the `Discord Developer Portal <https://discord.com/developers/applications>`_.
2. Select your application → **OAuth2 → URL Generator**.
3. Check **bot** scope and the permissions your bot needs.
4. Open the generated URL in your browser and invite it to a test server.

Next steps
----------

- :doc:`/basics` — options, intents, and caching.
- :doc:`/events` — the full list of gateway events.
- ``examples/`` folder in the package for real-world patterns.

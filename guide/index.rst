.. toctree::
   :hidden:

   index
   faq
   basics
   events/index
   repositories
   parts/index
   collection
   permissions
   message_builder
   components
   interactions

=====
Guide
=====

DiscordPHP is a wrapper for the Discord REST, WebSocket and Voice APIs. Built on top of `ReactPHP <https://reactphp.org/>`_ components. This documentation is based off the latest release.

Requirements
============

- `PHP 8.0<https://php.net>`_ or higher

  + Will not run on a webserver (FPM, CGI), you must run through CLI. A bot is a long-running process.
  + x86 (32-bit) PHP requires ``ext-gmp`` extension enabled.

- ``ext-json`` for JSON parsing.
- ``ext-zlib`` for gateway packet compression.

Recommended Extensions
----------------------

- One of ``ext-uv``, ``ext-ev`` or ``evt-event`` (in order of preference) for a faster, and more performant event loop.
- ``ext-mbstring`` if you may handle non-english characters.

Voice Requirements
==================

- 64-bit PHP
- ``ext-sodium`` for voice encryption.
- FFmpeg

Development Environment Recommendations
---------------------------------------

We recommend using an editor with support for the `Language Server Protocol<https://microsoft.github.io/language-server-protocol/>`_.
A list of supported editors can be found `here <https://microsoft.github.io/language-server-protocol/implementors/servers/>`_.
Here are some commonly used editors:

- Visual Studio Code (built-in LSP support)
- Vim/Neovim (with the `coc.nvim <https://github.com/neoclide/coc.nvim>`_ plugin for LSP support)
- PHPStorm (built-in PHP support)

We recommend installing `PHP Intelephense <https://intelephense.com/>`_ alongside your LSP-equipped editor for code completion alongside other helpful features. There is no need to pay for the premium features, the free version will suffice.

Installation
============

Installation requires `Composer <https://getcomposer.org>`_.

To install the latest release::

   $ composer require team-reflex/discord-php

If you would like to run on the latest ``master`` branch::

   $ composer require team-reflex/discord-php dev-master

``master`` can be substituted for any other branch name to install that branch.

Key Tips
========

As Discord is a real-time application, events come frequently and it is vital that your code does not block the ReactPHP event loop.
Most, if not all, functions return promises, therefore it is vital that you understand the concept of asynchronous programming with promises.
You can learn more about ReactPHP promises `here <https://reactphp.org/promise/>`_.

Help
====

If you need any help, feel free to join the `PHP Discorders <https://discord.gg/dphp>`_ Discord and someone should be able to give you a hand. We are a small community so please be patient if someone can't help you straight away.

Contributing
============

All contributions are welcome through pull requests in our GitHub repository. At the moment we would love contributions towards:

- Unit testing
- Documentation

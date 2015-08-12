# DiscordPHP

An API to interact with the popular messaging and voice app [Discord!](http://discordapp.com)

## Installation

To install, you will need [Composer](http://getcomposer.org). There is a basic setup guide [here.](https://getcomposer.org/doc/00-intro.md)

Once you have Composer set up for your project, run the command `composer require team-reflex/discord-php` in your projects main directory. This will start the download for DiscordPHP.

## Basic Usage

To use the API, you need an account from [Discord](http://discordapp.com). We suggest creating one solely for the API.

To create a Discord instance, do the following:
```php
<?php

use Discord\Discord;

// Email/Password for Discord
$email_address = 'email@email.com';
$password = 'my_password';

// Try log into Discord!
$discord = new Discord($email_address, $password);
```

This is just basic setup, more information can be found in the wiki!

## Todo
- Possibly add a cache for the authentication or make it a singleton?

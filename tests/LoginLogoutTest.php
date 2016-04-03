<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;

class LoginLogoutTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests logging into Discord.
     *
     * @return void
     */
    public function testLoggingIn()
    {
        $discord = new Discord(null, null, getenv('DISCORD_TOKEN'));
    }

    /**
     * Tests logging out of Discord.
     *
     * @return void
     */
    public function testLoggingOut()
    {
        $discord = new Discord(null, null, getenv('DISCORD_TOKEN'));

        $discord->logout();
    }
}

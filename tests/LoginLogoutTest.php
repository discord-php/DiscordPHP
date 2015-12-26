<?php

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
        $discord = new Discord(getenv('DISCORD_EMAIL'), getenv('DISCORD_PASSWORD'));
    }

    /**
     * Tests logging out of Discord.
     *
     * @return void 
     */
    public function testLoggingOut()
    {
        $discord = new Discord(getenv('DISCORD_EMAIL'), getenv('DISCORD_PASSWORD'));

        $discord->logout();
    }
}

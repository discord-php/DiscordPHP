<?php

use Discord\Discord;
use Monolog\Logger;

class DiscordSingleton
{
    private static $discord;

    /**
     * @return Discord
     */
    public static function get()
    {
        if (! self::$discord) {
            self::$discord = new Discord([
                'token' => getenv('DISCORD_TOKEN'),
                'loggerLevel' => Logger::EMERGENCY
            ]);
        }

        return self::$discord;
    }
}

<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

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
                'loggerLevel' => Logger::EMERGENCY,
            ]);
        }

        return self::$discord;
    }
}

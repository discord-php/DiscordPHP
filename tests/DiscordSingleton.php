<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Monolog\Handler\StreamHandler;
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
            $logger = new Logger('DiscordPHP-UnitTests');
            $logger->pushHandler(new StreamHandler(fopen(__DIR__.'/../phpunit.log', 'w')));

            self::$discord = new Discord([
                'token' => getenv('DISCORD_TOKEN'),
                'logger' => $logger,
                'httpLogger' => $logger,
            ]);
        }

        return self::$discord;
    }
}

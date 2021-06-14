<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Monolog\Formatter\LineFormatter;
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
            self::new();
        }

        return self::$discord;
    }

    private static function new()
    {
        $logger = new Logger('DiscordPHP-UnitTests');
        $handler = new StreamHandler(fopen(__DIR__.'/../phpunit.log', 'w'));
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $discord = new Discord([
            'token' => getenv('DISCORD_TOKEN'),
            'logger' => $logger,
        ]);

        $e = null;

        $timer = $discord->getLoop()->addTimer(10, function () use (&$e) {
            $e = new Exception('Timed out trying to connect to Discord.');
        });

        $discord->on('ready', function (Discord $discord) use ($timer) {
            $discord->getLoop()->cancelTimer($timer);
            $discord->getLoop()->stop();
        });

        $discord->getLoop()->run();

        if ($e !== null) {
            throw $e;
        }

        self::$discord = $discord;
    }
}

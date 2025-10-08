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
use Discord\WebSockets\Intents;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use React\EventLoop\Loop;

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

    private static function new_cache()
    {
        $redis = (new Clue\React\Redis\Factory())->createLazyClient('localhost:6379');
        $cache = new WyriHaximus\React\Cache\Redis($redis);

        //$cache = new WyriHaximus\React\Cache\Filesystem(React\Filesystem\Filesystem::create($loop), getenv('RUNNER_TEMP').DIRECTORY_SEPARATOR);

        //$memcached = new \Memcached();
        //$memcached->addServer('localhost', 11211);
        //$psr6Cache = new \Symfony\Component\Cache\Adapter\MemcachedAdapter($memcached, 'dphp', 0);
        //$cache = new RedisPsr16($psr6Cache);

        $logger = new Logger('DiscordPHP-UnitTests');
        $handler = new StreamHandler(fopen(__DIR__.'/../phpunit.log', 'w'));
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $discord = new Discord([
            'token' => getenv('DISCORD_TOKEN'),
            'intents' => Intents::getDefaultIntents(),
            'useTransportCompression' => false, // Disable zlib-stream
            'usePayloadCompression' => false, // RFC1950 2.2
            'logger' => $logger,
            'cache' => $cache,
        ]);

        $e = null;

        $timer = $discord->getLoop()->addTimer(10, function () use (&$e) {
            $e = new Exception('Timed out trying to connect to Discord.');
        });

        $discord->on('ready', function (Discord $discord) use ($timer) {
            $discord->getLoop()->cancelTimer($timer);
            $discord->getLoop()->stop();
        });

        self::$discord = $discord;

        self::$discord->run();

        if ($e !== null) {
            throw $e;
        }
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
            'intents' => Intents::getDefaultIntents(),
            'useTransportCompression' => false, // Disable zlib-stream
            'usePayloadCompression' => false, // RFC1950 2.2
        ]);

        $e = null;

        $timer = $discord->getLoop()->addTimer(10, function () use (&$e) {
            $e = new Exception('Timed out trying to connect to Discord.');
        });

        $discord->on('ready', function (Discord $discord) use ($timer) {
            $discord->getLoop()->cancelTimer($timer);
            $discord->getLoop()->stop();
        });

        self::$discord = $discord;

        self::$discord->run();

        if ($e !== null) {
            throw $e;
        }
    }
}

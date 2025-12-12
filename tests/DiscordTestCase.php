<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use PHPUnit\Framework\TestCase;

use function React\Promise\set_rejection_handler;

class DiscordTestCase extends TestCase
{
    protected static Channel $channel;

    public static function setUpBeforeClass(): void
    {
        set_rejection_handler(function (\Throwable $e): void {
            echo 'Uncaught Promise Rejection: ', $e->getMessage(), "\n";
        });

        /** @var Channel $channel */
        $channel = wait(function (Discord $discord, $resolve) {
            $channel = $discord->getChannel(getenv('TEST_CHANNEL'));
            $resolve($channel);
        });
        self::$channel = $channel;
        assert(self::$channel instanceof Channel, 'Channel not found. Please check your environment variables and ensure TEST_CHANNEL is set.');
    }

    protected function channel()
    {
        return self::$channel;
    }
}

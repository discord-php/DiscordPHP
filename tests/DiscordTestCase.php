<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
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
    protected static ?Channel $channel = null;

    public static function setUpBeforeClass(): void
    {
        // The clients the tests build send requests of their own, such as for the gateway, and
        // nothing waits on those. React calls the rejection handler once and then unsets it, so
        // this one sets itself again each time.
        $ignore = static function (\Throwable $e) use (&$ignore): void {
            set_rejection_handler($ignore);
        };
        set_rejection_handler($ignore);

        self::$channel = null;

        // Without a token, wait() runs on a mock client that never connects: tests that answer
        // requests with getMockHttpDriver() run as usual, and those that need the live test
        // channel skip themselves through channel().
        if (! self::isLive()) {
            return;
        }

        /** @var Channel|null $channel */
        try {
            $channel = wait(function (Discord $discord, $resolve) {
                $channel = $discord->getChannel(getenv('TEST_CHANNEL'));
                $resolve($channel);
            });
        } catch (\Throwable $e) {
            static::markTestSkipped('Could not connect to Discord: '.$e->getMessage());

            return;
        }

        if (! $channel instanceof Channel) {
            static::markTestSkipped('Channel not found. Please check your environment variables and ensure TEST_CHANNEL is set.');

            return;
        }

        self::$channel = $channel;
    }

    /**
     * The live test channel. Skips the test when there is none, as when DISCORD_TOKEN is not set.
     */
    protected function channel()
    {
        if (null === self::$channel) {
            $this->markTestSkipped('Needs a live connection to Discord: set DISCORD_TOKEN and TEST_CHANNEL.');
        }

        return self::$channel;
    }

    /**
     * Whether the tests run against Discord itself, with a bot token, rather than a mock client.
     */
    protected static function isLive(): bool
    {
        return ! in_array(getenv('DISCORD_TOKEN'), [false, ''], true);
    }
}

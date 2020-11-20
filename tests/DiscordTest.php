<?php

declare(strict_types=1);

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use PHPUnit\Framework\TestCase;

final class DiscordTest extends TestCase
{
    public function testCheckEnvVariablesPresent()
    {
        $this->assertNotFalse(getenv('DISCORD_TOKEN'), 'Discord token is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL'), 'Test channel ID is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL_NAME'), 'Test channel name is missing');
    }

    /**
     * @depends testCheckEnvVariablesPresent
     */
    public function testCanConnect()
    {
        return wait(function (Discord $discord, $resolve) {
            $discord->on('ready', function () use ($resolve) {
                $this->assertTrue(true);
                $resolve();
            });
        });
    }

    public function testCanGetChannel()
    {
        return wait(function (Discord $discord, $resolve) {
            $channel = $discord->getChannel(getenv('TEST_CHANNEL'));
            $this->assertEquals(getenv('TEST_CHANNEL_NAME'), $channel->name);

            $resolve($channel);
        });
    }
}

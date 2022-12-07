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
use PHPUnit\Framework\TestCase;

final class DiscordTest extends TestCase
{
    public function testCheckEnvVariablesPresent()
    {
        $this->assertNotFalse(getenv('DISCORD_TOKEN'), 'Discord token is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL'), 'Test channel ID is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL_NAME'), 'Test channel name is missing');
    }

    public function testSetGetCacheAsync()
    {
        wait(function (Discord $discord, $resolve) {
            $cache = $discord->getCacheConfig()->interface;
            $this->assertIsObject($cache, 'No CacheInterface set');
            $data = 'DiscordPHP 123';

            $cache->set('DPHP.Test', $data)->then(function ($success) use ($cache, $data) {
                $this->assertTrue($success, 'Failed to set a cache');
                if ($success) {
                    return $cache->get('DPHP.Test')->then(function ($value) use ($data) {
                        $this->assertEquals($data, $value, 'The stored cache mismatched');

                        return $value ? true : false;
                    });
                }

                return $success;
            })->done($resolve, $resolve);
        }, 10);
    }
}

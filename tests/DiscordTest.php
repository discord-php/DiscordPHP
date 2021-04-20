<?php

declare(strict_types=1);

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use PHPUnit\Framework\TestCase;

final class DiscordTest extends TestCase
{
    public function testCheckEnvVariablesPresent()
    {
        $this->assertNotFalse(getenv('DISCORD_TOKEN'), 'Discord token is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL'), 'Test channel ID is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL_NAME'), 'Test channel name is missing');
    }
}

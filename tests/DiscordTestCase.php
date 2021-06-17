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

class DiscordTestCase extends TestCase
{
    protected static Channel $channel;

    public static function setUpBeforeClass(): void
    {
        self::$channel = wait(function (Discord $discord, $resolve) {
            $channel = $discord->getChannel(getenv('TEST_CHANNEL'));
            $resolve($channel);
        });
    }

    protected function channel()
    {
        return self::$channel;
    }
}

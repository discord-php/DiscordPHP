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
use Discord\Parts\Gateway\GetGateway;
use Discord\Parts\Gateway\GetGatewayBot;

final class GetGatewayTest extends DiscordTestCase
{
    public function testTheGatewayUrlIsRead()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => ['url' => 'wss://gateway.discord.gg']);
            $mock->getHttpClient()->setDriver($driver);

            $mock->getGateway()
                ->then(function ($gateway) use ($driver) {
                    $this->assertInstanceOf(GetGateway::class, $gateway);
                    $this->assertSame('wss://gateway.discord.gg', $gateway->url);
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/gateway', $driver->requests[0]['url']);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testGetGatewayBotIsGetGatewayWithMore()
    {
        $bot = getMockDiscord()->getFactory()->part(GetGatewayBot::class, ['url' => 'wss://gateway.discord.gg', 'shards' => 2]);

        $this->assertInstanceOf(GetGateway::class, $bot);
        $this->assertSame(2, $bot->shards);
    }
}

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
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Events\ChannelDelete;

use function Discord\promiseFromGenerator;

/**
 * Parts a guild loads with it share its `created` flag, so deleting one must not mark the guild or its other parts deleted.
 */
final class DeletedChildTest extends DiscordTestCase
{
    public function testDeletingAChannelLeavesTheGuildAndOtherChannels()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn () => null));
            [$guild, $a, $b] = $this->guildWithTwoChannels($mock);

            $guild->channels->delete($a)
                ->then(function () use ($guild, $a, $b) {
                    $this->assertFalse($a->created);
                    $this->assertTrue($guild->created);
                    $this->assertTrue($b->created);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAChannelDeleteEventLeavesTheGuildAndOtherChannels()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            [$guild, $a, $b] = $this->guildWithTwoChannels($mock);
            $mock->guilds->pushItem($guild);

            promiseFromGenerator((new ChannelDelete($mock))->handle((object) ['id' => '101', 'guild_id' => '10', 'type' => Channel::TYPE_GUILD_TEXT]))
                ->then(function () use ($guild, $a, $b) {
                    $this->assertFalse($a->created);
                    $this->assertTrue($guild->created);
                    $this->assertTrue($b->created);
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * @return array{Guild, Channel, Channel}
     */
    private function guildWithTwoChannels(Discord $mock): array
    {
        $guild = $mock->getFactory()->part(Guild::class, ['id' => '10'], true);
        $guild->fill(['channels' => [
            (object) ['id' => '101', 'guild_id' => '10', 'type' => Channel::TYPE_GUILD_TEXT, 'name' => 'a'],
            (object) ['id' => '102', 'guild_id' => '10', 'type' => Channel::TYPE_GUILD_TEXT, 'name' => 'b'],
        ]]);

        return [$guild, $guild->channels->get('id', '101'), $guild->channels->get('id', '102')];
    }
}

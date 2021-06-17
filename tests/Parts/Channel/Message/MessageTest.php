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
use Discord\Parts\Channel\Message;

final class MessageTest extends DiscordTestCase
{
    /**
     * @covers \Discord\Parts\Channel\Message::getMentionChannelsAttribute
     */
    public function testCanMentionChannel()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('mention channel <#'.$this->channel()->id.'>')
                ->then(function (Message $message) {
                    $this->assertEquals(1, $message->mention_channels->count());
                    $this->assertInstanceOf(Channel::class, $message->mention_channels->first());
                    $this->assertEquals($this->channel()->id, $message->mention_channels->first()->id);
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Message::crosspost
     */
    public function testCanCrosspostMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('crossposting message')
                ->then(function (Message $message) {
                    return $message->crosspost();
                })
                ->then(function ($message) {
                    $this->assertInstanceOf(Message::class, $message);
                })
                ->done($resolve, $resolve);
        }, 10, function () {
            $this->markTestIncomplete('Crosspost has likely hit ratelimit.');
        });
    }
}

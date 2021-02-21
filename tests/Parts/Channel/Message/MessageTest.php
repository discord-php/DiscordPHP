<?php

declare(strict_types=1);

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;

final class MessageTest extends DiscordTestCase
{
    public function testCanMentionChannel()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('mention channel <#'.$this->channel()->id.'>')
            ->then(function (Message $message) use ($resolve) {
                $this->assertEquals(1, $message->mention_channels->count());
                $this->assertInstanceOf(Channel::class, $message->mention_channels->first());
                $this->assertEquals($this->channel()->id, $message->mention_channels->first()->id);
                $resolve();
            })
            ->done();
        });
    }

    public function testCanCrosspostMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('crossposting message')
            ->then(function (Message $message) use ($resolve) {
                return $message->crosspost();
            })
            ->done(function ($message) use ($resolve) {
                $this->assertInstanceOf(Message::class, $message);
                $resolve();
            });
        });
    }
}

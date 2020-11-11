<?php declare(strict_types=1);

use Discord\Discord;
use Discord\Parts\Channel\Message;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    /**
     * @depends DiscordTest::testCanGetChannel
     */
    public function testCanSendMessage(Discord $discord)
    {
        return wait($discord, function (Discord $discord, $channel, $resolve) {
            $content = 'Hello, world! From PHPunit';

            $channel->sendMessage($content)->done(function (Message $message) use ($resolve, $content) {
                $this->assertEquals($content, $message->content);
                $resolve($message);
            }, [$this, 'fail']);
        });
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCanEditMessage(Discord $discord)
    {
        return wait($discord, function (Discord $discord, Message $message, $resolve) {
            $content = 'Message edit with PHPunit';

            $message->content = $content;
            $message->channel->messages->save($message)->done(function (Message $message) use ($resolve, $content) {
                $this->assertEquals($content, $message->content);
                $resolve($message);
            }, [$this, 'fail']);
        });
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCanDeleteMessage(Discord $discord)
    {
        return wait($discord, function (Discord $discord, Message $message, $resolve) {
            $message->channel->messages->delete($message)->done(function (Message $message) use ($resolve) {
                $this->assertFalse($message->created);
                $resolve();
            }, [$this, 'fail']);
        });
    }
}

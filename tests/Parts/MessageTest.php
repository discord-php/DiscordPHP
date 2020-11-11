<?php declare(strict_types=1);

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    /**
     * @depends DiscordTest::testCanGetChannel
     */
    public function testCanSendMessage(Channel $channel)
    {
        return wait(function (Discord $discord, $resolve) use ($channel) {
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
    public function testCanEditMessage(Message $message)
    {
        return wait(function (Discord $discord, $resolve) use ($message) {
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
    public function testCanDeleteMessage(Message $message)
    {
        return wait(function (Discord $discord, $resolve) use ($message) {
            $message->channel->messages->delete($message)->done(function (Message $message) use ($resolve) {
                $this->assertFalse($message->created);
                $resolve();
            }, [$this, 'fail']);
        });
    }
}

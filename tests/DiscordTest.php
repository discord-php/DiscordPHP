<?php declare(strict_types=1);

use Discord\Discord;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

final class DiscordTest extends TestCase
{
    public function testCanConnect()
    {
        return wait(function (Discord $discord, $resolve) {
            $discord->on('ready', function () use ($resolve) {
                $this->assertTrue(false);
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

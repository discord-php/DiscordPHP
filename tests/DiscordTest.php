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
        $discord = DiscordSingleton::get();

        timeout('waitForReady', 5, $discord->getLoop())->done(function () {
            $this->fail('Ready event did not trigger within 5s');
        });

        $discord->on('ready', function (Discord $discord) {
            cancelTimeout('waitForReady', $discord->getLoop());
            $this->assertTrue(true);
            $discord->getLoop()->stop();
        });

        $discord->getLoop()->run();
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

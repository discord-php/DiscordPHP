<?php declare(strict_types=1);

use Discord\Discord;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

final class DiscordTest extends TestCase
{
    public function testCheckEnvVariablesPresent()
    {
        $this->assertNotFalse(getenv('DISCORD_TOKEN'), 'Discord token is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL'), 'Test channel ID is missing');
        $this->assertNotFalse(getenv('TEST_CHANNEL_NAME'), 'Test channel name is missing');
    }

    /**
     * @depends testCheckEnvVariablesPresent
     */
    public function testCanConnect()
    {
        return wait(function (Discord $discord, $resolve) {
            $discord->on('ready', function () use ($resolve) {
                $this->assertTrue(true);
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

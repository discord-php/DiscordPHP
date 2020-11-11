<?php declare(strict_types=1);

use Discord\Discord;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

final class DiscordTest extends TestCase
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Discord
     */
    private $discord;

    protected function setUp(): void
    {
        $this->loop = Factory::create();
        $this->discord = new Discord([
            'token' => getenv('DISCORD_TOKEN'),
            'loggerLevel' => Logger::ERROR,
            'loop' => $this->loop,
        ]);

        parent::setUp();
    }

    public function testCanConnect()
    {
        timeout('waitForReady', 5, $this->loop)->done(function () {
            $this->fail('Ready event did not trigger within 5s');
        });

        $this->discord->on('ready', function () {
            cancelTimeout('waitForReady', $this->loop);
            $this->assertTrue(true);
            $this->loop->stop();
        });

        $this->loop->run();
        return $this->discord;
    }

    /**
     * @depends testCanConnect
     */
    public function testCanGetChannel(Discord $discord)
    {
        return wait($discord, function (Discord $discord, $next, $resolve) {
            $channel = $discord->getChannel(getenv('TEST_CHANNEL'));
            $this->assertEquals(getenv('TEST_CHANNEL_NAME'), $channel->name);
            $resolve($channel);
        });
    }
}

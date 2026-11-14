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

use Discord\RPC\IpcClient;
use Discord\RPC\WindowsPipeConnector;
use Discord\RPC\WindowsPipeAttempt;
use React\Socket\Connector;

use function React\Promise\all;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Real native Windows pipes against a synthetic Node server; no Discord client or OAuth.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods) Independent transport regressions share the same native pipe fixture.
 */
final class WindowsPipeConnectorTest extends DiscordTestCase
{
    private StreamSelectLoop $loop;
    private string $node;
    private string $path = '\\\\.\\pipe\\discord-ipc-9';
    private $fixture = null;
    private ?string $readyFile = null;
    private array $connections = [];
    private ?IpcClient $client = null;

    protected static function isLive(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        $this->loop = new StreamSelectLoop();
        $this->node = getenv('DPHP_TEST_NODE') ?: '';
    }

    protected function tearDown(): void
    {
        $this->client?->close();
        foreach ($this->connections as $connection) {
            $connection->close();
        }
        // Allow terminated helpers to be reaped without blocking PHP on proc_close.
        $this->loop->addTimer(0.15, fn () => $this->loop->stop());
        $this->loop->run();
        if (is_resource($this->fixture)) {
            proc_terminate($this->fixture, 9);
            $until = microtime(true) + 2;
            while (proc_get_status($this->fixture)['running'] && microtime(true) < $until) {
                usleep(10000);
            }
            $this->assertFalse(proc_get_status($this->fixture)['running']);
            proc_close($this->fixture);
        }
        if ($this->readyFile !== null && file_exists($this->readyFile)) {
            unlink($this->readyFile);
        }
    }

    private function requireWindows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows' || $this->node === '') {
            $this->markTestSkipped('Requires Windows and DPHP_TEST_NODE absolute executable path.');
        }
    }

    /** @SuppressWarnings(PHPMD.UnusedLocalVariable) proc_open requires a by-reference pipes output even with file-only descriptors. */
    private function server(string $mode = 'rpc'): void
    {
        $this->requireWindows();
        $this->readyFile = sys_get_temp_dir().'/dphp-pipe-'.bin2hex(random_bytes(8));
        $this->fixture = proc_open([$this->node, __DIR__.'/fixtures/windows-pipe-server.cjs', $this->path, $this->readyFile, $mode], [
            0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w'],
        ], $unused, null, null, ['bypass_shell' => true]);
        $until = microtime(true) + 3;
        while (! file_exists($this->readyFile) && microtime(true) < $until && proc_get_status($this->fixture)['running']) {
            usleep(10000);
        }
        $this->assertFileExists($this->readyFile, 'Synthetic server must own pipe 9 before any connection; never fall back to Discord.');
    }

    private function await(PromiseInterface $promise): object
    {
        $result = (object) ['settled' => false, 'value' => null, 'error' => null];
        $watchdog = $this->loop->addTimer(5, fn () => $this->loop->stop());
        $promise->then(function ($value) use ($result) {
            $result->value = $value;
            $result->settled = true;
            $this->loop->stop();
        }, function (Throwable $error) use ($result) {
            $result->error = $error;
            $result->settled = true;
            $this->loop->stop();
        });
        if (! $result->settled) {
            $this->loop->run();
        }
        $this->loop->cancelTimer($watchdog);
        $this->assertTrue($result->settled, 'Pipe operation exceeded watchdog.');

        return $result;
    }

    private function connector(float $timeout = 2): WindowsPipeConnector
    {
        return new WindowsPipeConnector($this->node, $this->loop, $timeout);
    }

    public function testRejectsNetworkAndArbitraryPipeTargets(): void
    {
        $connector = new WindowsPipeConnector('/trusted/node', $this->loop);
        foreach (['tcp://127.0.0.1:1234', '\\\\remote\\pipe\\discord-ipc-0', '\\\\.\\pipe\\other', '\\\\.\\pipe\\discord-ipc-10', "\\\\.\\pipe\\discord-ipc-0\0"] as $uri) {
            $this->assertInstanceOf(InvalidArgumentException::class, $this->await($connector->connect($uri))->error);
        }
    }

    public function testMissingHelperRejectsWithoutBlockingLoop(): void
    {
        $this->requireWindows();
        $result = $this->await((new WindowsPipeConnector('C:\\missing-dphp-node.exe', $this->loop, 0.2))->connect($this->path));
        $this->assertInstanceOf(RuntimeException::class, $result->error);
        // A failed process spawn may reject synchronously; it must never wait on a pipe.
    }

    public function testUnauthenticatedLoopbackPeerIsClosedWithoutReceivingTraffic(): void
    {
        $this->server();
        $attempt = new WindowsPipeAttempt($this->loop, $this->node, 9, 2);
        $pending = $attempt->start();
        // Discover the internal ephemeral endpoint to simulate an unrelated local process.
        $listener = (new ReflectionProperty($attempt, 'server'))->getValue($attempt);
        $this->assertStringStartsWith('tcp://127.0.0.1:', $listener->getAddress());
        $received = '';
        $closed = new Deferred();
        $rogue = (new Connector(['dns' => false, 'tls' => false], $this->loop))->connect($listener->getAddress())->then(function ($peer) use (&$received, $closed) {
            $peer->on('data', function ($bytes) use (&$received) {
                $received .= $bytes;
            });
            $peer->on('close', fn () => $closed->resolve(true));
            $peer->write(str_repeat('x', 65));

            return $closed->promise();
        });
        $result = $this->await(all([$pending, $rogue]));
        $this->assertNull($result->error);
        $this->assertSame('', $received);
        $this->connections[] = $result->value[0];
        $result->value[0]->close();
        $this->loop->addTimer(0.15, fn () => $this->loop->stop());
        $this->loop->run();
        $this->assertNull((new ReflectionProperty($attempt, 'helper'))->getValue($attempt), 'Closed helpers must be reaped and release their poll timer.');
    }

    public function testCancellationAndConnectorDeadline(): void
    {
        $this->server();
        $pending = $this->connector()->connect($this->path);
        $pending->cancel();
        $this->assertStringContainsString('cancelled', $this->await($pending)->error->getMessage());
        $expired = $this->await($this->connector(0.0001)->connect($this->path));
        $this->assertStringContainsString('deadline', $expired->error->getMessage());
        $reconnected = $this->await($this->connector()->connect($this->path));
        $this->assertNull($reconnected->error);
        $this->connections[] = $reconnected->value;
    }

    public function testFragmentedReadyCorrelationAndReconnectCleanup(): void
    {
        $this->server();
        $this->client = new IpcClient($this->loop, $this->connector(), ['paths' => [$this->path]]);
        for ($i = 0; $i < 2; ++$i) {
            $ready = $this->await($this->client->connect('123456789'));
            $this->assertNull($ready->error);
            $reply = $this->await($this->client->request('GET_GUILDS'));
            $this->assertSame(['synthetic' => true], $reply->value['data']);
            $this->client->close();
        }
    }

    public function testReadyDeadlineRejectsQueuedRequests(): void
    {
        $this->server('silent');
        $this->client = new IpcClient($this->loop, $this->connector(), ['paths' => [$this->path], 'connect_timeout' => 0.5]);
        $connecting = $this->client->connect('123456789');
        $queuedError = null;
        $this->client->request('GET_GUILDS')->then(null, function ($error) use (&$queuedError) {
            $queuedError = $error;
        });
        $ticks = 0;
        $heartbeat = $this->loop->addPeriodicTimer(0.01, function () use (&$ticks) {
            ++$ticks;
        });
        $result = $this->await($connecting);
        $this->loop->cancelTimer($heartbeat);
        $this->assertGreaterThan(0, $ticks, 'The loop must continue servicing timers while waiting for native READY.');
        $this->assertSame('IPC READY deadline exceeded.', $result->error->getMessage());
        $this->assertSame($queuedError, $result->error);
    }

    public function testRealPipeBackpressureAndBinaryIntegrity(): void
    {
        $this->server('echo');
        $result = $this->await($this->connector()->connect($this->path));
        $this->assertNull($result->error);
        $connection = $result->value;
        $this->connections[] = $connection;
        $bytes = str_repeat("\0\xffdphp", 1398102);
        $received = '';
        $drained = false;
        $finished = new Deferred();
        $connection->on('drain', function () use (&$drained) {
            $drained = true;
        });
        $connection->on('data', function ($chunk) use (&$received, $bytes, $finished) {
            $received .= $chunk;
            if (strlen($received) >= strlen($bytes)) {
                $finished->resolve(true);
            }
        });
        $this->assertFalse($connection->write($bytes), 'Large write must expose ReactPHP backpressure.');
        $this->assertNull($this->await($finished->promise())->error);
        $this->assertTrue($drained);
        $this->assertSame(hash('sha256', $bytes), hash('sha256', $received));
    }
}

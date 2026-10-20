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

use Discord\RPC\CloseException;
use Discord\RPC\CommandException;
use Discord\RPC\FrameDecoder;
use Discord\RPC\IpcClient;
use Discord\RPC\ProtocolException;
use Discord\RPC\TokenExchangeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectorInterface;

use function React\Promise\resolve;

require_once __DIR__.'/FakeConnection.php';

final class IpcClientTest extends DiscordTestCase
{
    private LoopInterface $loop;
    private array $ticks = [];
    private array $timers = [];
    private array $attempts = [];
    private array $paths = [];
    private IpcClient $client;
    private FakeRpcConnection $connection;

    protected static function isLive(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        $this->ticks = $this->timers = $this->paths = [];
        $this->loop = $this->createStub(LoopInterface::class);
        $this->loop->method('futureTick')->willReturnCallback(function ($callback) {
            $this->ticks[] = $callback;
        });
        $this->loop->method('addTimer')->willReturnCallback(function ($interval, $callback) {
            $timer = $this->createStub(TimerInterface::class);
            $timer->method('getInterval')->willReturn((float) $interval);
            $timer->method('getCallback')->willReturn($callback);
            $this->timers[spl_object_id($timer)] = $timer;

            return $timer;
        });
        $this->loop->method('cancelTimer')->willReturnCallback(function ($timer) {
            unset($this->timers[spl_object_id($timer)]);
        });
        $this->connection = new FakeRpcConnection();
        $this->attempts = [resolve($this->connection)];
        $this->client = $this->newClient();
    }

    protected function tearDown(): void
    {
        $this->client->close();
        $this->assertSame([], $this->timers, 'All owned deadlines must be cancelled.');
    }

    private function newClient(array $options = []): IpcClient
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('connect')->willReturnCallback(function ($uri) {
            $this->paths[] = $uri;

            return array_shift($this->attempts);
        });

        return new IpcClient($this->loop, $connector, $options + ['paths' => ['unix:///synthetic/discord-ipc-0']]);
    }

    private function tick(): void
    {
        $callbacks = $this->ticks;
        $this->ticks = [];
        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    private function flush(): void
    {
        for ($i = 0; $this->ticks && $i < 100; ++$i) {
            $this->tick();
        }
        $this->assertSame([], $this->ticks, 'The synthetic loop must become idle.');
    }

    private function deadline(float $interval): void
    {
        foreach ($this->timers as $id => $timer) {
            if ($timer->getInterval() === $interval && isset($this->timers[$id])) {
                unset($this->timers[$id]);
                ($timer->getCallback())($timer);
            }
        }
    }

    private function observe(PromiseInterface $promise): object
    {
        $result = (object) ['settled' => false, 'value' => null, 'error' => null];
        $promise->then(function ($value) use ($result) {
            $result->settled = true;
            $result->value = $value;
        }, function ($error) use ($result) {
            $result->settled = true;
            $result->error = $error;
        });

        return $result;
    }

    private function ready(): void
    {
        $result = $this->observe($this->client->connect('123456789'));
        $this->flush();
        $this->connection->receive(['cmd' => 'DISPATCH', 'evt' => 'READY', 'data' => ['v' => 1]]);
        $this->flush();
        $this->assertSame($this->client, $result->value);
    }

    private function answer(int $write, array $data = [], ?string $event = null, ?string $command = null): void
    {
        $request = $this->connection->payload($write);
        $this->connection->receive(['cmd' => $command ?? $request['cmd'], 'evt' => $event, 'nonce' => $request['nonce'], 'data' => $data]);
        $this->flush();
    }

    public function testFirstFrameFragmentsReadyAndReleasesQueuedRequest(): void
    {
        $connecting = $this->observe($this->client->connect('123456789'));
        $request = $this->observe($this->client->request('GET_GUILDS'));
        $this->flush();
        $this->assertFalse($connecting->settled);
        $this->assertCount(1, $this->connection->writes);
        $this->assertSame(['v' => 1, 'client_id' => '123456789'], $this->connection->payload(0));
        $frame = FrameDecoder::encode(1, '{"cmd":"DISPATCH","evt":"READY","data":{}}');
        $this->connection->emit('data', [substr($frame, 0, 7)]);
        $this->flush();
        $this->assertFalse($connecting->settled);
        $this->connection->emit('data', [substr($frame, 7)]);
        $this->flush();
        $this->assertSame($this->client, $connecting->value);
        $this->assertCount(2, $this->connection->writes);
        $this->answer(1, ['guilds' => []]);
        $this->assertSame(['guilds' => []], $request->value['data']);
    }

    public function testOutOfOrderResponsesSubscriptionAcksErrorsAndDispatch(): void
    {
        $this->ready();
        $first = $this->observe($this->client->request('GET_GUILDS'));
        $second = $this->observe($this->client->request('SUBSCRIBE', ['channel_id' => '1'], 'MESSAGE_CREATE'));
        $third = $this->observe($this->client->request('AUTHENTICATE', ['access_token' => 'synthetic-token']));
        $events = [];
        $this->client->on('dispatch', function ($event, $data) use (&$events) {
            $events[] = [$event, $data];
        });
        $this->answer(2, [], 'MESSAGE_CREATE');
        $this->assertTrue($second->settled);
        $this->assertFalse($first->settled);
        $this->answer(3, ['code' => 4999, 'message' => 'synthetic-token'], 'ERROR');
        $this->assertInstanceOf(CommandException::class, $third->error);
        $this->assertSame(4999, $third->error->getCode());
        $this->assertSame('AUTHENTICATE', $third->error->getCommand());
        $this->assertStringNotContainsString('synthetic-token', $third->error->getMessage());
        $this->answer(1);
        $this->answer(1, ['late' => true]);
        $this->connection->receive(['cmd' => 'DISPATCH', 'evt' => 'MESSAGE_CREATE', 'data' => ['id' => '5']]);
        $this->flush();
        $this->assertSame([['MESSAGE_CREATE', ['id' => '5']]], $events);
    }

    public function testCommandAndSubscriptionEventMismatchRejectOnlyTheirRequest(): void
    {
        $this->ready();
        $first = $this->observe($this->client->request('GET_GUILDS'));
        $this->answer(1, [], null, 'GET_CHANNEL');
        $this->assertInstanceOf(ProtocolException::class, $first->error);
        $second = $this->observe($this->client->request('SUBSCRIBE', [], 'MESSAGE_CREATE'));
        $this->answer(2, [], 'MESSAGE_UPDATE');
        $this->assertInstanceOf(ProtocolException::class, $second->error);
        $third = $this->observe($this->client->request('UNSUBSCRIBE', [], 'MESSAGE_CREATE'));
        $this->answer(3, [], 'MESSAGE_CREATE');
        $this->assertNull($third->error);
        $this->assertTrue($third->settled);
    }

    public function testPingPongAndFairReceiveScheduling(): void
    {
        $this->client = $this->newClient(['frames_per_tick' => 1]);
        $this->ready();
        $events = [];
        $pongs = [];
        $this->client->on('dispatch', function ($event) use (&$events) {
            $events[] = $event;
        });
        $this->client->on('pong', function ($payload) use (&$pongs) {
            $pongs[] = $payload;
        });
        $this->connection->emit('data', [FrameDecoder::encode(3, "\0ping\xff").FrameDecoder::encode(4, 'pong').FrameDecoder::encode(1, '{"cmd":"DISPATCH","evt":"ACTIVITY_INVITE","data":{}}')]);
        $this->tick();
        $this->assertSame(FrameDecoder::encode(4, "\0ping\xff"), $this->connection->writes[1]);
        $this->assertSame([], $events);
        $this->assertSame([], $pongs);
        $this->flush();
        $this->assertSame(['pong'], $pongs);
        $this->assertSame(['ACTIVITY_INVITE'], $events);
        $this->client->sendPing('probe');
        $this->assertSame(FrameDecoder::encode(3, 'probe'), $this->connection->writes[2]);
    }

    public function testBackpressureDoesNotDuplicateAcceptedWritesAndCancellationRemovesQueuedFrames(): void
    {
        $this->ready();
        $this->connection->acceptMore = false;
        $first = $this->observe($this->client->request('GET_GUILDS'));
        $cancelled = $this->client->request('GET_CHANNEL', ['channel_id' => '1']);
        $cancelledResult = $this->observe($cancelled);
        $second = $this->observe($this->client->request('GET_CHANNELS'));
        $this->assertCount(2, $this->connection->writes);
        $cancelled->cancel();
        $this->assertNotNull($cancelledResult->error);
        $this->connection->acceptMore = true;
        $this->connection->emit('drain');
        $this->assertCount(3, $this->connection->writes);
        $this->assertSame('GET_CHANNELS', $this->connection->payload(2)['cmd']);
        $this->answer(1);
        $this->answer(2);
        $this->assertTrue($first->settled && $second->settled);
    }

    public function testDeadlinesAndResourceLimits(): void
    {
        $this->client = $this->newClient(['max_pending_requests' => 1]);
        $connecting = $this->observe($this->client->connect('123'));
        $first = $this->observe($this->client->request('GET_GUILDS', [], null, 2.0));
        $overflow = $this->observe($this->client->request('GET_CHANNELS'));
        $this->assertInstanceOf(OverflowException::class, $overflow->error);
        $this->deadline(2.0);
        $this->assertStringContainsString('deadline', $first->error->getMessage());
        $this->flush();
        $this->assertCount(1, $this->connection->writes);
        $this->deadline(10.0);
        $this->assertStringContainsString('READY deadline', $connecting->error->getMessage());
        $this->assertSame([], $this->connection->listeners('data'));
    }

    public function testOutboundQueueAndFrameLimits(): void
    {
        $this->client = $this->newClient(['max_frame_bytes' => 128, 'max_queue_bytes' => 128]);
        $this->ready();
        $this->connection->acceptMore = false;
        $first = $this->observe($this->client->request('GET_GUILDS'));
        $queued = $this->observe($this->client->request('GET_CHANNELS'));
        $overflow = $this->observe($this->client->request('GET_GUILDS'));
        $this->assertInstanceOf(OverflowException::class, $overflow->error);
        $large = $this->observe($this->client->request('SET_ACTIVITY', ['secret' => str_repeat('x', 256)]));
        $this->assertInstanceOf(OverflowException::class, $large->error);
        $this->client->close();
        $this->assertNotNull($first->error);
        $this->assertNotNull($queued->error);
    }

    #[DataProvider('invalidPayloads')]
    public function testInvalidInboundPayloadClosesAndRejectsPending(string $payload, int $opcode = 1): void
    {
        $this->ready();
        $request = $this->observe($this->client->request('GET_GUILDS'));
        $errors = [];
        $this->client->on('error', function ($error) use (&$errors) {
            $errors[] = $error;
        });
        $this->connection->emit('data', [FrameDecoder::encode($opcode, $payload)]);
        $this->flush();
        $this->assertInstanceOf(ProtocolException::class, $request->error);
        $this->assertCount(1, $errors);
        $this->assertSame(1, $this->connection->closes);
    }

    public static function invalidPayloads(): array
    {
        return [['{'], ['[]'], ['null'], ['{"cmd":true}'], ['{"cmd":"GET_GUILDS"}'], ['{"cmd":"DISPATCH","evt":"READY"}'], ['{"code":"4000","message":"bad"}', 2], ['{}', 99], [str_repeat('[', 65).'0'.str_repeat(']', 65)]];
    }

    public function testCloseCodeIsSeparateFromCommandErrorsAndCloseIsIdempotent(): void
    {
        $this->ready();
        $request = $this->observe($this->client->request('GET_GUILDS'));
        $closes = [];
        $this->client->on('close', function ($error) use (&$closes) {
            $closes[] = $error;
        });
        $this->connection->receive(['code' => 4004, 'message' => 'untrusted reason'], 2);
        $this->flush();
        $this->connection->emit('end');
        $this->connection->emit('close');
        $this->client->close();
        $this->assertCount(1, $closes);
        $this->assertSame($request->error, $closes[0]);
        $this->assertInstanceOf(CloseException::class, $request->error);
        $this->assertSame(4004, $request->error->getCode());
        $this->assertSame('untrusted reason', $request->error->getReason());
        $this->assertStringNotContainsString('untrusted reason', $request->error->getMessage());
    }

    public function testCancelledConnectClosesLateSocketAndReconnectHasFreshIdentity(): void
    {
        $late = new Deferred(); // Deliberately ignores cancellation.
        $this->attempts = [$late->promise()];
        $promise = $this->client->connect('123');
        $old = $this->observe($promise);
        $queued = $this->observe($this->client->request('GET_GUILDS'));
        $this->flush();
        $promise->cancel();
        $this->assertNotNull($old->error);
        $this->assertNotNull($queued->error);
        $lateSocket = new FakeRpcConnection();
        $late->resolve($lateSocket);
        $this->assertSame(1, $lateSocket->closes);
        $this->attempts = [resolve($this->connection)];
        $fresh = $this->observe($this->client->connect('456'));
        $this->flush();
        $this->assertSame(['v' => 1, 'client_id' => '456'], $this->connection->payload(0));
        $this->connection->receive(['cmd' => 'DISPATCH', 'evt' => 'READY']);
        $this->flush();
        $this->assertSame($this->client, $fresh->value);
    }

    public function testPathFallbackAndLocalPathDiscovery(): void
    {
        $this->attempts = [\React\Promise\reject(new RuntimeException('synthetic')), resolve($this->connection)];
        $this->client = $this->newClient(['paths' => ['unix:///one/discord-ipc-0', 'unix:///two/discord-ipc-1']]);
        $connecting = $this->observe($this->client->connect('123'));
        $this->flush();
        $this->assertSame(['unix:///one/discord-ipc-0', 'unix:///two/discord-ipc-1'], $this->paths);
        $this->connection->receive(['cmd' => 'DISPATCH', 'evt' => 'READY']);
        $this->flush();
        $this->assertTrue($connecting->settled);
        $this->assertSame('unix:///run/user/1/discord-ipc-0', IpcClient::getCandidatePaths(['XDG_RUNTIME_DIR' => '/run/user/1', 'TMPDIR' => '/run/user/1', 'TMP' => 'relative'], false)[0]);
        $this->assertCount(20, IpcClient::getCandidatePaths(['XDG_RUNTIME_DIR' => '/run/user/1', 'TMPDIR' => '/run/user/1'], false));
        $windows = IpcClient::getCandidatePaths([], true);
        $this->assertCount(10, $windows);
        $this->assertSame('\\\\?\\pipe\\discord-ipc-0', $windows[0]);
        $this->newClient(['paths' => $windows]); // Validation works on both host platforms.
    }

    public function testInjectedOAuthPreservesContextIsAsyncAndPrintsNoToken(): void
    {
        $this->expectOutputString('');
        $this->ready();
        $exchangeResult = new Deferred();
        $exchange = $this->getMockBuilder(TokenExchangeInterface::class)->getMock();
        $exchange->expects($this->once())->method('exchange')->with('synthetic-code', '123456789', 'http://127.0.0.1/exact?state=one')->willReturn($exchangeResult->promise());
        $result = $this->observe($this->client->authorizeAndAuthenticate(['rpc', 'identify'], 'http://127.0.0.1/exact?state=one', $exchange));
        $this->assertSame('123456789', $this->connection->payload(1)['args']['client_id']);
        $this->answer(1, ['code' => 'synthetic-code']);
        $this->assertFalse($result->settled);
        $progress = false;
        $this->loop->futureTick(function () use (&$progress) {
            $progress = true;
        });
        $this->flush();
        $this->assertTrue($progress);
        $exchangeResult->resolve('synthetic-access-token');
        $this->assertSame('synthetic-access-token', $this->connection->payload(2)['args']['access_token']);
        $this->answer(2, ['user' => ['id' => '7']]);
        $this->assertSame(['user' => ['id' => '7']], $result->value['data']);
        $this->assertSame([], $this->client->listeners('close'));
    }

    #[DataProvider('oauthStops')]
    public function testOAuthCancellationDeadlineAndCloseIgnoreLateExchange(string $stop): void
    {
        $this->ready();
        $cancelled = 0;
        $pending = new Deferred(function () use (&$cancelled) {
            ++$cancelled;
        });
        $exchange = $this->createStub(TokenExchangeInterface::class);
        $exchange->method('exchange')->willReturn($pending->promise());
        $promise = $this->client->authorizeAndAuthenticate(['rpc'], 'http://127.0.0.1', $exchange, 7.0);
        $result = $this->observe($promise);
        $this->answer(1, ['code' => 'synthetic']);
        $duplicate = $this->observe($this->client->authorizeAndAuthenticate(['rpc'], 'http://127.0.0.1', $exchange));
        $this->assertInstanceOf(LogicException::class, $duplicate->error);
        match ($stop) {
            'cancel' => $promise->cancel(), 'timeout' => $this->deadline(7.0), 'close' => $this->client->close()
        };
        $this->assertNotNull($result->error);
        $this->assertSame(1, $cancelled);
        $pending->resolve('synthetic-late-token');
        $this->flush();
        $this->assertCount(2, $this->connection->writes, 'Late token must not authenticate.');
        $this->assertSame([], $this->client->listeners('close'));
    }

    public static function oauthStops(): array
    {
        return [['cancel'], ['timeout'], ['close']];
    }

    public function testOAuthExchangeFailureDoesNotLeakProviderBodyOrCode(): void
    {
        $this->ready();
        $exchange = $this->createStub(TokenExchangeInterface::class);
        $exchange->method('exchange')->willReturn(\React\Promise\reject(new RuntimeException('provider body: synthetic-token and synthetic-code')));
        $result = $this->observe($this->client->authorizeAndAuthenticate(['rpc'], 'http://127.0.0.1', $exchange));
        $this->answer(1, ['code' => 'synthetic-code']);
        $this->assertSame('RPC OAuth exchange failed.', $result->error->getMessage());
        $this->assertNull($result->error->getPrevious());
        $this->assertCount(2, $this->connection->writes);
    }
}

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
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\UnixServer;

use function React\Promise\all;

/** Real local Unix sockets against a synthetic server; never connects to Discord. */
final class IpcSocketTest extends DiscordTestCase
{
    private ?StreamSelectLoop $loop = null;
    private ?UnixServer $server = null;
    private ?ConnectionInterface $peer = null;
    private ?IpcClient $client = null;
    private ?string $directory = null;
    private ?\Closure $respond = null;
    private array $frames = [];

    protected static function isLive(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        if (! in_array('unix', stream_get_transports(), true)) {
            $this->markTestSkipped('PHP has no Unix socket transport; this does not test Windows native pipes.');
        }
        $this->loop = new StreamSelectLoop();
        $this->directory = sys_get_temp_dir().'/dphp-rpc-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($this->directory, 0700));
        $this->server = new UnixServer($this->directory.'/ipc.sock', $this->loop);
        $this->server->on('connection', function (ConnectionInterface $connection) {
            $this->peer = $connection;
            $buffer = '';
            $connection->on('data', function (string $bytes) use (&$buffer, $connection) {
                $buffer .= $bytes;
                while (($frame = $this->extract($buffer)) !== null) {
                    $this->frames[] = $frame;
                    ($this->respond)($frame[0], $frame[1], $connection);
                }
            });
        });
    }

    protected function tearDown(): void
    {
        $this->client?->close();
        $this->peer?->close();
        $this->server?->close();
        if ($this->directory !== null) {
            if (file_exists($this->directory.'/ipc.sock')) {
                unlink($this->directory.'/ipc.sock');
            }
            rmdir($this->directory);
        }
    }

    /** The synthetic server also accepts client HANDSHAKE, which the inbound decoder correctly rejects. */
    private function extract(string &$buffer): ?array
    {
        if (strlen($buffer) < 8) {
            return null;
        }
        $header = unpack('Vopcode/Vlength', substr($buffer, 0, 8));
        $this->assertLessThanOrEqual(65536, $header['length']);
        if (strlen($buffer) < 8 + $header['length']) {
            return null;
        }
        $payload = substr($buffer, 8, $header['length']);
        $buffer = substr($buffer, 8 + $header['length']);

        return [$header['opcode'], $payload];
    }

    /** Drive the real loop, with a hard stop so a framing regression cannot hang CI. */
    private function await(PromiseInterface $promise): object
    {
        $result = (object) ['settled' => false, 'value' => null, 'error' => null];
        $deadline = $this->loop->addTimer(3.0, fn () => $this->loop->stop());
        $promise->then(function ($value) use ($result) {
            $result->value = $value;
            $result->settled = true;
            $this->loop->stop();
        }, function (Throwable $error) use ($result) {
            $result->error = $error;
            $result->settled = true;
            $this->loop->stop();
        });
        $this->loop->run();
        $this->loop->cancelTimer($deadline);
        $this->assertTrue($result->settled, 'The real Unix socket exchange must finish before the watchdog.');

        return $result;
    }

    public function testDefaultUnixConnectorHandlesFragmentedReadyPingAndCorrelatedReplies(): void
    {
        $handshake = null;
        $requests = [];
        $pong = new Deferred();
        $this->respond = function (int $opcode, string $payload, ConnectionInterface $connection) use (&$handshake, &$requests, $pong) {
            if ($opcode === 0) {
                $handshake = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
                $ready = '{"cmd":"DISPATCH","evt":"READY","data":{"v":1}}';
                $wire = pack('VV', 1, strlen($ready)).$ready;
                $connection->write(substr($wire, 0, 5));
                $this->loop->addTimer(0.01, fn () => $connection->write(substr($wire, 5).pack('VV', 3, 6)."\0ping\xff"));

                return;
            }
            if ($opcode === 4) {
                $pong->resolve($payload);

                return;
            }
            $this->assertSame(1, $opcode);
            $requests[] = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
            if (count($requests) === 2) {
                foreach (array_reverse($requests) as $request) {
                    $reply = json_encode(['cmd' => $request['cmd'], 'nonce' => $request['nonce'], 'data' => ['echo' => $request['args']]], JSON_THROW_ON_ERROR);
                    $connection->write(pack('VV', 1, strlen($reply)).$reply);
                }
            }
        };
        $this->client = new IpcClient($this->loop, null, ['paths' => ['unix://'.$this->directory.'/missing.sock', 'unix://'.$this->directory.'/ipc.sock']]);
        $commands = $this->client->connect('123456789')->then(fn () => all([
            $this->client->request('GET_GUILDS'),
            $this->client->request('GET_CHANNEL', ['channel_id' => '456']),
        ]));
        $result = $this->await(all([$commands, $pong->promise()]));
        $this->assertNull($result->error);
        $this->assertSame(['v' => 1, 'client_id' => '123456789'], $handshake);
        $this->assertSame('GET_GUILDS', $result->value[0][0]['cmd']);
        $this->assertSame(['channel_id' => '456'], $result->value[0][1]['data']['echo']);
        $this->assertSame("\0ping\xff", $result->value[1]);
        $this->assertCount(4, $this->frames);
    }

    public function testMissingReadyRejectsQueuedWorkAndClosesRealSocket(): void
    {
        $closed = new Deferred();
        $this->server->on('connection', fn (ConnectionInterface $connection) => $connection->on('close', fn () => $closed->resolve(true)));
        $this->respond = static function () {
        };
        $this->client = new IpcClient($this->loop, null, ['paths' => ['unix://'.$this->directory.'/ipc.sock'], 'connect_timeout' => 0.25]);
        $connecting = $this->client->connect('123456789')->then(null, static fn (Throwable $error) => $error);
        $queued = $this->client->request('GET_GUILDS')->then(null, static fn (Throwable $error) => $error);
        $result = $this->await(all([$connecting, $queued, $closed->promise()]));
        $this->assertNull($result->error);
        $this->assertInstanceOf(RuntimeException::class, $result->value[0]);
        $this->assertSame('IPC READY deadline exceeded.', $result->value[0]->getMessage());
        $this->assertSame($result->value[0], $result->value[1]);
        $this->assertTrue($result->value[2]);
        $this->assertCount(1, $this->frames, 'A command queued before READY must never reach the socket.');
        $this->assertSame(0, $this->frames[0][0]);
    }
}

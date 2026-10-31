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

use Discord\Discord;
use Discord\WebSockets\Op;
use Psr\Log\NullLogger;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

final class GatewayContinuityTest extends DiscordTestCase
{
    public static function setUpBeforeClass(): void
    {
        // This suite never constructs a network client, even with a live test token.
    }

    private function client(): Discord
    {
        $client = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connectWs'])
            ->getMock();
        $client->expects($this->never())->method('connectWs');
        $loop = $this->createStub(LoopInterface::class);
        $loop->method('addPeriodicTimer')->willReturn($this->createStub(TimerInterface::class));
        $loop->method('addTimer')->willReturn($this->createStub(TimerInterface::class));
        $this->set($client, 'loop', $loop);
        $this->set($client, 'logger', new NullLogger());

        return $client;
    }

    private function socket(): WebSocket
    {
        return $this->getMockBuilder(WebSocket::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['close'])
            ->getMock();
    }

    private function set(Discord $client, string $name, $value): void
    {
        (new ReflectionProperty(Discord::class, $name))->setValue($client, $value);
    }

    public function testConnectionStateDoesNotRequireSessionReadiness(): void
    {
        $client = $this->client();
        $this->assertFalse($client->isConnected());
        $socket = $this->socket();
        $socket->expects($this->never())->method('close');
        $client->handleWsConnection($socket);
        // No READY/RESUMED or init event is needed to observe an open transport.
        $this->assertTrue($client->isConnected());
    }

    public function testNormalAndCriticalClosesNotifyOnceAfterCleanupEvenWhenListenerThrows(): void
    {
        foreach ([1000, 1006, 4004] as $code) {
            $client = $this->client();
            $socket = $this->socket();
            $socket->expects($this->never())->method('close');
            $client->handleWsConnection($socket);
            $loop = $this->getMockBuilder(LoopInterface::class)->getMock();
            $loop->expects($this->exactly(3))->method('cancelTimer');
            if ($code === 4004) {
                $loop->expects($this->never())->method('addTimer');
            } else {
                $loop->expects($this->once())->method('addTimer')->with(2, $this->isCallable())
                    ->willReturn($this->createStub(TimerInterface::class));
            }
            $this->set($client, 'loop', $loop);
            foreach (['heartbeatTimer', 'heartbeatAckTimer', 'payloadTimer'] as $name) {
                $this->set($client, $name, $this->createStub(TimerInterface::class));
            }
            $calls = 0;
            $failure = new RuntimeException('listener failure');
            $client->on('disconnected', function (int $actual, string $reason, Discord $client) use ($code, &$calls, $failure): void {
                ++$calls;
                $this->assertSame($code, $actual);
                $this->assertSame('test close', $reason);
                $this->assertFalse($client->isConnected());
                foreach (['heartbeatTimer', 'heartbeatAckTimer', 'payloadTimer'] as $name) {
                    $this->assertNull((new ReflectionProperty(Discord::class, $name))->getValue($client));
                }
                throw $failure;
            });
            try {
                $socket->emit('close', [$code, 'test close']);
                $this->fail('The listener exception must propagate after cleanup.');
            } catch (RuntimeException $caught) {
                $this->assertSame($failure, $caught);
            }
            // Closing suppresses the pre-existing reconnect behavior on a duplicate callback.
            $this->set($client, 'closing', true);
            $client->handleWsClose($code, 'duplicate');
            $this->assertSame(1, $calls);
        }
    }

    public function testExplicitCloseInvalidatesBeforeSocketCloseAndNotifiesAfterShutdown(): void
    {
        foreach ([false, true] as $closeLoop) {
            $client = $this->client();
            $socket = $this->socket();
            $client->handleWsConnection($socket);
            $events = [];
            $loop = $this->getMockBuilder(LoopInterface::class)->getMock();
            $loop->expects($closeLoop ? $this->once() : $this->never())->method('stop')
                ->willReturnCallback(function () use (&$events): void {
                    $events[] = 'stop';
                });
            // The delayed socket callback cancels the transport's payload timer.
            $loop->expects($this->once())->method('cancelTimer');
            $this->set($client, 'loop', $loop);
            $client->on('closed', function () use (&$events): void {
                $events[] = 'closed';
            });
            $failure = new RuntimeException('listener failure');
            $client->on('disconnected', function (int $code, string $reason, Discord $client) use (&$events, $closeLoop, $failure): void {
                $this->assertFalse($client->isConnected());
                $this->assertSame($closeLoop ? Op::CLOSE_UNKNOWN_ERROR : Op::CLOSE_NORMAL, $code);
                $this->assertSame('discordphp closing...', $reason);
                $this->assertSame($closeLoop ? ['socket', 'closed', 'stop'] : ['socket', 'closed'], $events);
                $events[] = 'disconnected';
                throw $failure;
            });
            $code = $closeLoop ? Op::CLOSE_UNKNOWN_ERROR : Op::CLOSE_NORMAL;
            $socket->expects($this->once())->method('close')->with($code, 'discordphp closing...')
                ->willReturnCallback(function () use ($client, &$events): void {
                    $this->assertFalse($client->isConnected());
                    $events[] = 'socket';
                });
            try {
                $client->close($closeLoop);
                $this->fail('The listener exception must propagate after shutdown.');
            } catch (RuntimeException $caught) {
                $this->assertSame($failure, $caught);
            }
            $socket->emit('close', [$code, 'delayed transport close']);
            $this->assertSame($closeLoop ? ['socket', 'closed', 'stop', 'disconnected'] : ['socket', 'closed', 'disconnected'], $events);
        }
    }

    public function testExplicitCloseWithImmediateSocketCallbackDoesNotDuplicateNotification(): void
    {
        $client = $this->client();
        $socket = $this->socket();
        $client->handleWsConnection($socket);
        $socket->expects($this->once())->method('close')
            ->willReturnCallback(function (int $code, string $reason) use ($socket): void {
                $socket->emit('close', [$code, $reason]);
            });
        $calls = 0;
        $client->on('disconnected', function () use (&$calls): void {
            ++$calls;
        });
        $client->close(false);
        $this->assertSame(1, $calls);
    }

    public function testDisconnectedListenerCanCloseClientWithoutRecursiveNotification(): void
    {
        $client = $this->client();
        $socket = $this->socket();
        $client->handleWsConnection($socket);
        $socket->expects($this->once())->method('close')
            ->willReturnCallback(function (int $code, string $reason) use ($socket): void {
                $socket->emit('close', [$code, $reason]);
            });
        $calls = 0;
        $client->on('disconnected', function (int $code, string $reason, Discord $client) use (&$calls): void {
            ++$calls;
            $this->assertFalse($client->isConnected());
            $client->close(false);
        });
        $socket->emit('close', [4004, 'critical close']);
        $this->assertSame(1, $calls);
        $this->assertFalse($client->isConnected());
    }

    public function testExplicitDisconnectListenerCanCloseAgainWithoutRecursiveNotification(): void
    {
        $client = $this->client();
        $socket = $this->socket();
        $client->handleWsConnection($socket);
        $socket->expects($this->exactly(2))->method('close');
        $calls = 0;
        $client->on('disconnected', function (int $code, string $reason, Discord $client) use (&$calls): void {
            ++$calls;
            $this->assertFalse($client->isConnected());
            $client->close(false);
        });
        $client->close(false);
        $this->assertSame(1, $calls);
    }

    public function testClosingBeforeConnectionDoesNotEmitDisconnect(): void
    {
        $client = $this->client();
        $events = [];
        $client->on('disconnected', function () use (&$events): void {
            $events[] = 'disconnected';
        });
        $client->close(false);
        $socket = $this->socket();
        $socket->expects($this->once())->method('close')->with(Op::CLOSE_NORMAL, 'discordphp closing...');
        $client->handleWsConnection($socket);
        $this->assertSame([], $events);
        $this->assertFalse($client->isConnected());
    }

    public function testReplacementTransportCanDisconnectIndependently(): void
    {
        $client = $this->client();
        $calls = 0;
        $client->on('disconnected', function () use (&$calls): void {
            ++$calls;
        });
        foreach ([1000, 4004] as $code) {
            $socket = $this->socket();
            $socket->expects($this->never())->method('close');
            $client->handleWsConnection($socket);
            $this->assertTrue($client->isConnected());
            $socket->emit('close', [$code, 'test close']);
            $this->assertFalse($client->isConnected());
        }
        $this->assertSame(2, $calls);
    }
}

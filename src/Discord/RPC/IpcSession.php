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

namespace Discord\RPC;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;

use function React\Promise\reject;

/**
 * Owns one local socket and its bounded frame decoder, without interpreting RPC payloads.
 * Events: frame(int opcode, string payload), close(\Throwable error).
 *
 * @internal
 */
final class IpcSession extends EventEmitter
{
    private ?ConnectorInterface $connector;
    private FrameDecoder $decoder;
    private string $state = 'disconnected';
    private int $generation = 0;
    private ?ConnectionInterface $connection = null;
    private ?Deferred $opening = null;
    private ?PromiseInterface $attempt = null;
    private array $streamListeners = [];
    private bool $reading = false;

    /** Options have already been validated by the owning client. */
    public function __construct(private LoopInterface $loop, ?ConnectorInterface $connector, private array $options)
    {
        $this->connector = $connector ?? (PHP_OS_FAMILY === 'Windows' ? null : new Connector(['tcp' => false, 'tls' => false, 'dns' => false], $loop));
        $this->decoder = new FrameDecoder($options['max_frame_bytes'], $options['max_buffer_bytes']);
    }

    /** @return PromiseInterface<ConnectionInterface> Socket with frame and termination listeners installed. */
    public function open(): PromiseInterface
    {
        if ($this->state !== 'disconnected') {
            return reject(new \LogicException('IPC session is already connecting or connected.'));
        }
        if ($this->connector === null) {
            return reject(new \RuntimeException('Windows IPC requires an injected asynchronous native pipe connector.'));
        }
        $generation = ++$this->generation;
        $this->state = 'connecting';
        $this->opening = new Deferred(function () use ($generation) {
            if ($generation === $this->generation) {
                $this->disconnect(new \RuntimeException('IPC connection cancelled.'));
            }
        });
        $promise = $this->opening->promise();
        $this->loop->futureTick(fn () => $this->tryPath($generation, 0));

        return $promise;
    }

    public function close(): void
    {
        $this->disconnect(new \RuntimeException('IPC session closed.'));
    }

    private function tryPath(int $generation, int $index): void
    {
        if ($generation !== $this->generation || $this->state !== 'connecting') {
            return;
        }
        if (($this->options['paths'][$index] ?? null) === null) {
            $this->disconnect(new \RuntimeException('No local Discord IPC socket is available.'));

            return;
        }
        try {
            $attempt = $this->connector->connect($this->options['paths'][$index]);
            if ($generation !== $this->generation) {
                $attempt->then(static fn (ConnectionInterface $connection) => $connection->close(), static function () {
                });
                $attempt->cancel();

                return;
            }
            $this->attempt = $attempt;
            $attempt->then(fn (ConnectionInterface $connection) => $this->accept($connection, $generation), function () use ($generation, $index) {
                if ($generation === $this->generation) {
                    $this->attempt = null;
                    $this->loop->futureTick(fn () => $this->tryPath($generation, $index + 1));
                }
            });
        } catch (\Throwable $error) {
            if ($generation === $this->generation) {
                $this->disconnect(new \RuntimeException('IPC connection setup failed.'));
            }
        }
    }

    private function accept(ConnectionInterface $connection, int $generation): void
    {
        if ($generation !== $this->generation || $this->state !== 'connecting') {
            $connection->close();

            return;
        }
        $this->attempt = null;
        $this->connection = $connection;
        $this->state = 'connected';
        try {
            $this->attach($connection, $generation);
        } catch (\Throwable $error) {
            $this->disconnect(new \RuntimeException('IPC connection setup failed.'));

            return;
        }
        if ($generation !== $this->generation) {
            return;
        }
        $opening = $this->opening;
        $this->opening = null;
        $opening?->resolve($connection);
    }

    private function attach(ConnectionInterface $connection, int $generation): void
    {
        $this->streamListeners = [
            'data' => function (string $bytes) use ($generation) {
                if ($generation !== $this->generation) {
                    return;
                }
                try {
                    $this->decoder->append($bytes);
                    $this->scheduleRead($generation);
                } catch (ProtocolException $error) {
                    $this->disconnect(new ProtocolException('Invalid IPC framing.'));
                }
            },
            'end' => fn () => $generation === $this->generation ? $this->disconnect(new \RuntimeException('IPC stream ended.')) : null,
            'close' => fn () => $generation === $this->generation ? $this->disconnect(new \RuntimeException('IPC stream closed.')) : null,
            'error' => fn () => $generation === $this->generation ? $this->disconnect(new \RuntimeException('IPC stream failed.')) : null,
        ];
        foreach ($this->streamListeners as $event => $listener) {
            $connection->on($event, $listener);
        }
    }

    private function scheduleRead(int $generation): void
    {
        if ($this->reading) {
            return;
        }
        $this->reading = true;
        $this->loop->futureTick(fn () => $this->read($generation));
    }

    private function read(int $generation): void
    {
        if ($generation !== $this->generation) {
            return;
        }
        $this->reading = false;
        for ($count = 0; $count < $this->options['frames_per_tick']; $count++) {
            try {
                $frame = $this->decoder->next();
            } catch (ProtocolException $error) {
                $this->disconnect(new ProtocolException('Invalid IPC framing.'));

                return;
            }
            if ($frame === null) {
                return;
            }
            $this->emit('frame', $frame);
            if ($generation !== $this->generation) {
                return;
            }
        }
        $this->scheduleRead($generation);
    }

    private function disconnect(\Throwable $error): void
    {
        if ($this->state === 'disconnected' || $this->state === 'closing') {
            return;
        }
        ++$this->generation;
        $this->state = 'closing';
        $connection = $this->connection;
        $this->connection = null;
        foreach ($this->streamListeners as $event => $listener) {
            $connection?->removeListener($event, $listener);
        }
        $this->streamListeners = [];
        $attempt = $this->attempt;
        $this->attempt = null;
        $opening = $this->opening;
        $this->opening = null;
        $this->reading = false;
        $this->decoder->clear();
        $attempt?->cancel();
        $connection?->close();
        $this->state = 'disconnected';
        try {
            $this->emit('close', [$error]);
        } finally {
            $opening?->reject($error);
        }
    }
}

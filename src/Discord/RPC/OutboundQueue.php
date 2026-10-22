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
use React\Socket\ConnectionInterface;

/**
 * @internal Bounded, cancellable frames awaiting READY or transport drain.
 * @SuppressWarnings(PHPMD.StaticAccess) FrameDecoder is a stateless wire codec.
 * @since 10.69.0
 */
final class OutboundQueue extends EventEmitter
{
    private ?ConnectionInterface $connection = null;
    private array $frames = [];
    private int $bytes = 0;
    private int $generation = 0;
    private int $controlSequence = 0;
    private bool $ready = false;
    private bool $blocked = false;
    private bool $pumping = false;
    private bool $scheduled = false;
    private ?\Closure $drain = null;

    public function __construct(private LoopInterface $loop, private array $options)
    {
    }

    public function bind(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
        $this->drain = function () {
            $this->blocked = false;
            $this->pump();
        };
        $connection->on('drain', $this->drain);
        $this->pump();
    }

    public function activate(): void
    {
        $this->ready = true;
        $this->pump();
    }

    public function send(string $key, string $bytes, bool $requiresReady = true): void
    {
        if (strlen($bytes) > $this->options['max_frame_bytes'] + 8) {
            throw new \OverflowException('IPC frame limit exceeded.');
        }
        if (strlen($bytes) > $this->options['max_queue_bytes'] - $this->bytes || count($this->frames) >= $this->options['max_pending_requests'] + 32) {
            throw new \OverflowException('IPC outbound queue limit exceeded.');
        }
        $this->frames[$key] = ['bytes' => $bytes, 'requiresReady' => $requiresReady];
        $this->bytes += strlen($bytes);
        $this->pump();
    }

    public function control(int $opcode, string $payload): void
    {
        try {
            $this->send('control:'.++$this->controlSequence, FrameDecoder::encode($opcode, $payload), false);
        } catch (\OverflowException $error) {
            $this->emit('error', [$error]);
        }
    }

    public function cancel(string $key): void
    {
        if (isset($this->frames[$key])) {
            $this->bytes -= strlen($this->frames[$key]['bytes']);
            unset($this->frames[$key]);
        }
    }

    public function close(): void
    {
        ++$this->generation;
        $this->connection?->removeListener('drain', $this->drain);
        $this->connection = null;
        $this->drain = null;
        $this->frames = [];
        $this->bytes = 0;
        $this->ready = $this->blocked = $this->pumping = $this->scheduled = false;
    }

    private function shift(): ?string
    {
        foreach ($this->frames as $key => $frame) {
            if ($frame['requiresReady'] && ! $this->ready) {
                continue;
            }
            $this->cancel($key);

            return $frame['bytes'];
        }

        return null;
    }

    private function pump(): void
    {
        if ($this->connection === null || $this->blocked || $this->pumping) {
            return;
        }
        $this->pumping = true;
        $generation = $this->generation;
        try {
            $this->writeBatch($generation);
        } catch (\Throwable $error) {
            if ($generation === $this->generation) {
                $this->emit('error', [new \RuntimeException('IPC write failed.')]);
            }
        } finally {
            if ($generation === $this->generation) {
                $this->pumping = false;
            }
        }
    }

    private function writeBatch(int $generation): void
    {
        for ($count = 0; $count < $this->options['frames_per_tick']; ++$count) {
            $bytes = $this->shift();
            if ($bytes === null) {
                return;
            }
            // React write(false) accepted the entire frame; wait for drain without replaying it.
            $accepted = $this->connection->write($bytes);
            if ($generation !== $this->generation) {
                return;
            }
            $this->blocked = ! $accepted;
            if ($this->blocked) {
                return;
            }
        }
        if (! $this->scheduled) {
            $this->scheduled = true;
            $this->loop->futureTick(function () use ($generation) {
                if ($generation === $this->generation) {
                    $this->scheduled = false;
                    $this->pump();
                }
            });
        }
    }
}

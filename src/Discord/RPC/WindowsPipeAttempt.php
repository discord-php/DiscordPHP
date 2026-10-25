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

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

/** @internal Owns exactly one helper process, listener and connection attempt. */
final class WindowsPipeAttempt
{
    private ?SocketServer $server = null;
    private ?TimerInterface $deadline = null;
    private ?TimerInterface $poll = null;
    private array $peers = [];
    private ?ConnectionInterface $connection = null;
    private Deferred $deferred;
    private bool $settled = false;
    private bool $closing = false;
    /** @var resource|null */
    private $process = null;

    public function __construct(private LoopInterface $loop, private string $node, private int $index, private float $timeout)
    {
        $this->deferred = new Deferred(fn () => $this->close(new \RuntimeException('Windows pipe connection cancelled.')));
    }

    public function start(): PromiseInterface
    {
        try {
            // Bind before spawning: no port reservation race, hostname resolution or wildcard bind.
            $this->server = new SocketServer('127.0.0.1:0', [], $this->loop);
            $secret = bin2hex(random_bytes(32));
            $port = parse_url($this->server->getAddress(), PHP_URL_PORT);
            $this->server->on('connection', fn (ConnectionInterface $peer) => $this->accept($peer, $secret));
            $this->server->on('error', fn () => $this->close(new \RuntimeException('Windows pipe listener failed.')));
            $this->deadline = $this->loop->addTimer($this->timeout, fn () => $this->close(new \RuntimeException('Windows pipe connection deadline exceeded.')));
            // Never inherit NODE_OPTIONS, NODE_PATH, OAuth secrets or other application environment.
            $env = ['DPHP_RPC_PORT' => (string) $port, 'DPHP_RPC_SECRET' => $secret, 'DPHP_RPC_INDEX' => (string) $this->index,
                'DPHP_RPC_TIMEOUT' => (string) max(1, (int) ceil($this->timeout * 1000))];
            foreach (['SystemRoot', 'TEMP', 'TMP'] as $key) {
                if (getenv($key) !== false) {
                    $env[$key] = getenv($key);
                }
            }
            // No anonymous process pipes: these are not selectable on Windows. No shell or secret argv.
            $this->process = @proc_open([$this->node, __DIR__.'/bridge/windows-pipe.cjs'], [
                0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w'],
            ], $unused, null, $env, ['bypass_shell' => true, 'create_no_window' => true]);
            if (! is_resource($this->process)) {
                throw new \RuntimeException('Windows pipe helper could not start.');
            }
            $this->poll = $this->loop->addPeriodicTimer(0.02, fn () => $this->checkProcess());
        } catch (\Throwable $error) {
            $this->close(new \RuntimeException('Windows pipe helper could not start.'));
        }

        return $this->deferred->promise();
    }

    private function accept(ConnectionInterface $peer, string $secret): void
    {
        if ($this->closing || count($this->peers) >= 8) {
            $peer->close();

            return;
        }
        $id = spl_object_id($peer);
        $this->peers[$id] = $peer;
        $buffer = '';
        $listener = null;
        $listener = function (string $bytes) use ($peer, $secret, &$buffer, &$listener) {
            $buffer .= $bytes;
            if (strlen($buffer) < 65) {
                return;
            }
            if (! hash_equals($secret, substr($buffer, 0, 64)) || $buffer[64] !== '1') {
                $peer->close();

                return;
            }
            $peer->pause();
            $peer->removeListener('data', $listener);
            $this->connection = $peer;
            $this->settled = true;
            $this->releaseListener();
            $this->deferred->resolve($peer);
            $tail = substr($buffer, 65);
            $this->loop->futureTick(function () use ($peer, $tail) {
                if (! $this->closing && $peer->isReadable()) {
                    if ($tail !== '') {
                        $peer->emit('data', [$tail]);
                    }
                    $peer->resume();
                }
            });
        };
        $peer->on('data', $listener);
        $peer->on('error', static function () {
        });
        $peer->on('close', function () use ($id, $peer) {
            unset($this->peers[$id]);
            if ($peer === $this->connection) {
                $this->close(new \RuntimeException('Windows pipe connection closed.'));
            }
        });
    }

    private function releaseListener(): void
    {
        $this->server?->close();
        $this->server = null;
        if ($this->deadline !== null) {
            $this->loop->cancelTimer($this->deadline);
            $this->deadline = null;
        }
        foreach ($this->peers as $peer) {
            if ($peer !== $this->connection) {
                $peer->close();
            }
        }
    }

    private function checkProcess(): void
    {
        if (is_resource($this->process) && ! proc_get_status($this->process)['running']) {
            // proc_close can wait: only reap after the OS reports termination.
            proc_close($this->process);
            $this->process = null;
            $this->loop->cancelTimer($this->poll);
            $this->poll = null;
            $this->close(new \RuntimeException('Windows pipe helper exited.'));
        }
    }

    private function close(\Throwable $error): void
    {
        if ($this->closing) {
            return;
        }
        $this->closing = true;
        $this->releaseListener();
        $this->connection?->close();
        if (is_resource($this->process)) {
            // Termination is nonblocking; the poll timer reaps it after exit.
            proc_terminate($this->process, 9);
        }
        if (! $this->settled) {
            $this->settled = true;
            $this->deferred->reject($error);
        }
    }
}

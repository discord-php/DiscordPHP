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

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectorInterface;

use function React\Promise\reject;

/**
 * Opt-in native Windows pipe transport via a bundled Node.js helper.
 *
 * The helper uses libuv for pipe I/O. PHP watches only a loopback TCP socket.
 * Supply an absolute path to a trusted Node.js executable (22 or newer).
 * No RPC protocol or OAuth logic is implemented by this connector.
 *
 * @SuppressWarnings(PHPMD.StaticAccess) Optional global-loop default matches IpcClient.
 */
final class WindowsPipeConnector implements ConnectorInterface
{
    private LoopInterface $loop;

    public function __construct(private string $nodeBinary, ?LoopInterface $loop = null, private float $timeout = 5.0)
    {
        if (! preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $nodeBinary) || str_contains($nodeBinary, "\0") || ! is_finite($timeout) || $timeout <= 0 || $timeout > 300) {
            throw new \InvalidArgumentException('An absolute Node.js executable path and timeout between zero (exclusive) and 300 seconds are required.');
        }
        $this->loop = $loop ?? Loop::get();
    }

    /** @return PromiseInterface<\React\Socket\ConnectionInterface> Native pipe connected, before RPC READY. */
    public function connect($uri): PromiseInterface
    {
        if (! is_string($uri) || ! preg_match('~^\\\\\\\\[?.]\\\\pipe\\\\discord-ipc-([0-9])$~D', $uri, $match)) {
            return reject(new \InvalidArgumentException('Only local discord-ipc-0 through discord-ipc-9 pipes are accepted.'));
        }
        if (PHP_OS_FAMILY !== 'Windows' || ! function_exists('proc_open')) {
            return reject(new \RuntimeException('The Windows pipe helper requires Windows CLI PHP with proc_open enabled.'));
        }

        return (new WindowsPipeAttempt($this->loop, $this->nodeBinary, (int) $match[1], $this->timeout))->start();
    }
}

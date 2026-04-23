<?php

declare(strict_types=1);

namespace Discord\RPC;

use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use React\Promise\PromiseInterface;

/**
 * Factory for creating an appropriate connector for the current platform.
 *
 * On Unix-like systems this returns a `React\Socket\Connector` that supports unix domain sockets.
 * On Windows it will try to return a `WindowsPipeConnector` if available (third-party/native helper),
 * otherwise it returns null to signal that Windows async named-pipe support is not present.
 * 
 * @since TBD
 */
class ConnectorFactory
{
    public static function create(LoopInterface $loop)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            if (class_exists(WindowsPipeConnector::class)) {
                return new WindowsPipeConnector($loop);
            }

            // No Windows connector available — return null so callers can decide.
            return null;
        }

        return new Connector($loop);
    }
}

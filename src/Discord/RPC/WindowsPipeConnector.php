<?php

declare(strict_types=1);

namespace Discord\RPC;

use Evenement\EventEmitterTrait;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Abstract Windows named-pipe connector placeholder.
 *
 * This class provides the API surface for an async Windows named-pipe connector but
 * intentionally does not implement the native/third-party integration. It will reject
 * connect attempts and provide an actionable error message instructing how to add support.
 *
 * To enable Windows async named-pipe support, either:
 * - Install a PHP extension or library that provides a `WindowsPipeConnector` class that
 *   implements `connect(string $uri): PromiseInterface`, or
 * - Provide a native helper that proxies a named pipe to stdio and implement a connector
 *   that starts that helper and exposes a Duplex stream.
 * 
 * @since TBD
 */
class WindowsPipeConnector
{
    protected LoopInterface $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Attempt to connect to a named-pipe URI asynchronously.
     * Currently rejects with an informative error instructing how to enable support.
     *
     * @param string $uri Named pipe URI (e.g. `\\\\?\\pipe\\discord-ipc-0`)
     * @return PromiseInterface
     */
    public function connect(string $uri): PromiseInterface
    {
        $d = new Deferred();
        // Option 1: if a TCP proxy is provided, connect to it using React's Connector (non-blocking)
        $tcpProxy = getenv('DISCORD_IPC_TCP_PROXY');
        if ($tcpProxy !== false && $tcpProxy !== '') {
            // Expect value like "127.0.0.1:8080" or "127.0.0.1:0" etc.
            $parts = explode(':', $tcpProxy);
            if (count($parts) !== 2) {
                $d->reject(new \RuntimeException('Invalid DISCORD_IPC_TCP_PROXY value, expected host:port'));
                return $d->promise();
            }
            [$host, $port] = $parts;
            $connector = new \React\Socket\Connector($this->loop);
            $uri = 'tcp://' . $host . ':' . $port;
            return $connector->connect($uri);
        }

        // Option 2: spawn a helper (DISCORD_IPC_PROXY) that proxies pipe<->stdio (legacy fallback)
        $proxy = getenv('DISCORD_IPC_PROXY');
        if ($proxy === false || $proxy === '') {
            $msg = "Async Windows named-pipe IPC is not available.\n";
            $msg .= "To enable, either: (1) run our TCP proxy and set DISCORD_IPC_TCP_PROXY=127.0.0.1:PORT,\n";
            $msg .= "or (2) provide a helper program that proxies a Windows named pipe to stdio and set DISCORD_IPC_PROXY to its command.\n";
            $msg .= "Example helper usage: DISCORD_IPC_PROXY=\"node C:\\path\\to\\ipc-proxy.js\"\n";
            $d->reject(new \RuntimeException($msg));
            return $d->promise();
        }

        $cmd = $proxy . ' ' . escapeshellarg($uri);

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = @proc_open($cmd, $descriptors, $pipes);
        if ($process === false || !is_resource($process)) {
            $d->reject(new \RuntimeException('Failed to start DISCORD_IPC_PROXY helper: ' . $cmd));
            return $d->promise();
        }

        // Non-blocking streams
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[0], false);

        $loop = $this->loop;

        $wrapper = new class($process, $pipes, $loop) {
            use EventEmitterTrait;

            private $process;
            private $pipes;
            private $loop;

            public function __construct($process, $pipes, LoopInterface $loop)
            {
                $this->process = $process;
                $this->pipes = $pipes;
                $this->loop = $loop;

                $stdout = $pipes[1];

                $this->loop->addReadStream($stdout, function ($stream) {
                    $data = stream_get_contents($stream);
                    if ($data !== false && $data !== '') {
                        $this->emit('data', [$data]);
                    }
                    if (feof($stream)) {
                        $this->emit('close');
                        $this->loop->removeReadStream($stream);
                    }
                });
            }

            public function write(string $data): void
            {
                fwrite($this->pipes[0], $data);
            }

            public function end(): void
            {
                @fclose($this->pipes[0]);
                @fclose($this->pipes[1]);
                @fclose($this->pipes[2]);
                @proc_terminate($this->process);
            }
        };

        $d->resolve($wrapper);
        return $d->promise();
    }
}

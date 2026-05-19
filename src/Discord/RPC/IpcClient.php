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

use Evenement\EventEmitterTrait;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use Discord\RPC\ConnectorFactory;
use React\Socket\ConnectionInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Async IPC RPC client using ReactPHP.
 *
 * - Connects to Discord's local RPC server using Unix domain sockets (async via ReactPHP Connector).
 * - Emits "frame" events when a complete RPC frame is received: `['opcode', length, payloadString]`.
 * - Use `sendCommand()` to send RPC commands.
 *
 * Note: ReactPHP's Connector does not provide cross-platform named-pipe support for Windows
 * by default. On Windows, connecting to Discord IPC pipes is not implemented here and will
 * throw a RuntimeException. If Windows named-pipe support is required, integrate a platform
 * specific async connector (or use a small native extension / library that exposes non-blocking
 * named-pipe streams to ReactPHP).
 *
 * @link https://discord.com/developers/docs/topics/rpc#ipc-transport
 * 
 * @since TBD
 */
class IpcClient
{
    use EventEmitterTrait;

    protected LoopInterface $loop;
    protected $connector;
    protected $conn = null;
    protected string $buffer = '';
    /**
     * State used by the async connect attempt.
     * 
     * @var string[]
     */
    protected array $paths = [];
    protected int $tryIndex = 0;
    protected ?Deferred $connectDeferred = null;
    protected ?string $connectClientId = null;
    protected int $connectVersion = 1;
    /**
     * Whether we've observed the READY dispatch from the server.
     * Commands may be queued until ready.
     *
     * @var bool
     */
    protected bool $ready = false;

    /**
     * Pending command JSON strings to send once READY is observed.
     * 
     * @var string[]
     */
    protected array $pendingCommands = [];
    /**
     * Pending authorize requests keyed by nonce.
     * 
     * @var array<string, array{deferred:Deferred, timer:mixed}>
     */
    protected array $pendingAuthorizations = [];

    public function __construct(?LoopInterface $loop = null, $connector = null)
    {
        $this->loop = $loop ?? Loop::get();
        $created = ConnectorFactory::create($this->loop);
        if ($connector !== null) {
            $this->connector = $connector;
        } elseif ($created !== null) {
            $this->connector = $created;
        } else {
            // On Windows when no WindowsPipeConnector is available, leave connector null so connect() can
            // fail with a clear message.
            $this->connector = null;
        }

        // Internal listener to handle responses for pending operations (authorize, etc.)
        $this->on('response', [$this, 'handleResponseEvent']);
    }

    /**
     * Connect asynchronously to the first available IPC socket.
     * Resolves the promise when connected and handshake has been sent.
     * 
     * @param string $clientId The client ID to use in the handshake.
     * @param int    $version  The RPC protocol version to use (default: 1).
     * 
     * @throws \RuntimeException if no IPC socket is available or if async connection fails.
     * 
     * @return PromiseInterface<self>
     */
    public function connect(string $clientId, int $version = 1): PromiseInterface
    {
        if ($this->connector === null) {
            $d = new Deferred();
            $d->reject(new \RuntimeException('Async Windows named-pipe IPC is not supported by this client; install a Windows async connector or provide one via ConnectorFactory.'));

            return $d->promise();
        }

        $this->paths = $this->getCandidatePaths();
        $this->tryIndex = 0;
        $this->connectDeferred = new Deferred();
        $this->connectClientId = $clientId;
        $this->connectVersion = $version;

        // Kick off the async connect attempts via the event loop.
        $this->loop->futureTick([$this, 'tryNextConnect']);

        return $this->connectDeferred->promise();
    }

    /**
     * Attempt to connect to the next candidate path. Uses class state instead of reference captures.
     */
    public function tryNextConnect(): void
    {
        if ($this->connectDeferred === null) {
            return;
        }

        if ($this->tryIndex >= count($this->paths)) {
            $d = $this->connectDeferred;
            $this->clearConnectState();
            $d->reject(new \RuntimeException('No IPC socket available'));
            return;
        }

        $path = $this->paths[$this->tryIndex++];
        $uri = 'unix://' . $path;

        $this->connector->connect($uri)->then(function ($conn) {
            if ($this->connectDeferred === null) {
                $conn->end();
                return;
            }

            $this->conn = $conn;
            $this->setupConnection($conn);

            // send handshake
            $payload = json_encode(['v' => $this->connectVersion, 'client_id' => $this->connectClientId]);
            $this->writeFrame(0, $payload);

            $d = $this->connectDeferred;
            $this->clearConnectState();
            $d->resolve($this);
        }, function () {
            // try the next socket on the next loop tick
            $this->loop->futureTick([$this, 'tryNextConnect']);
        });
    }

    protected function clearConnectState(): void
    {
        $this->paths = [];
        $this->tryIndex = 0;
        $this->connectClientId = null;
        $this->connectVersion = 1;
        $this->connectDeferred = null;
    }

    /**
     * Close the IPC connection if it is open. Emits "close" event if the connection was open.
     */
    public function close(): void
    {
        if ($this->conn !== null) {
            if (is_object($this->conn) && method_exists($this->conn, 'end')) {
                $this->conn->end();
            } elseif (is_object($this->conn) && method_exists($this->conn, 'close')) {
                $this->conn->close();
            }
            $this->conn = null;
        }
    }

    /**
     * Generate candidate IPC socket paths based on environment variables and common defaults.
     * 
     * @return string[] List of candidate IPC socket paths to try connecting to.
     */
    protected function getCandidatePaths(): array
    {
        $candidates = [];
        $envs = ['XDG_RUNTIME_DIR', 'TMPDIR', 'TMP', 'TEMP'];
        foreach ($envs as $e) {
            $val = getenv($e);
            if ($val !== false) {
                for ($i = 0; $i < 10; $i++) {
                    $candidates[] = $val.DIRECTORY_SEPARATOR.'discord-ipc-'.$i;
                }
            }
        }
        for ($i = 0; $i < 10; $i++) {
            $candidates[] = '/tmp/discord-ipc-'.$i;
        }

        return $candidates;
    }

    /**
     * Set up event handlers for the IPC connection to read frames and handle close events.
     * 
     * @param ConnectionInterface $conn The IPC connection to set up.
     */
    protected function setupConnection($conn): void
    {
        $this->buffer = '';
        $conn->on('data', function (string $data) use ($conn) {
            $this->buffer .= $data;
            $this->processBufferAsync()->then(null, function ($e) {
                $this->emit('error', [$e]);
            });
        });

        $conn->on('end', function () {
            $this->emit('close');
            $this->conn = null;
        });

        $conn->on('close', function () {
            $this->emit('close');
            $this->conn = null;
        });

        $conn->on('error', function ($e) {
            $this->emit('error', [$e]);
        });
    }
    /**
     * Process the internal buffer to extract complete frames. Emits "frame" events for each complete frame found.
     * 
     * @return PromiseInterface
     */
    protected function processBufferAsync(): PromiseInterface
    {
        // Process a single frame (if available), then yield and continue via promises to avoid
        // long synchronous loops when many frames arrive at once.
        $d = new Deferred();

        return $d->promise()->then(function () {
            if (strlen($this->buffer) < 8) {
                return null;
            }

            $header = substr($this->buffer, 0, 8);
            $arr = @unpack('Vopcode/Vlen', $header);
            if ($arr === false) {
                return null;
            }

            $len = $arr['len'];
            $total = 8 + $len;
            if (strlen($this->buffer) < $total) {
                return null; // wait for more data
            }

            $payload = substr($this->buffer, 8, $len);
            $this->buffer = substr($this->buffer, $total);

            $opcode = (int) $arr['opcode'];

            // Emit raw frame for consumers who want the low-level bytes
            $this->emit('frame', [$opcode, $len, $payload]);

            // Handle opcodes per RPC spec
            if ($opcode === 1) {
                // FRAME: payload is JSON RPC payload
                $decoded = json_decode($payload, true);
                if ($decoded === null) {
                    $this->emit('error', [new \RuntimeException('Failed to decode FRAME payload as JSON: ' . json_last_error_msg())]);
                } else {
                    // Dispatch events (server -> client notifications) contain 'evt'
                    if (isset($decoded['evt']) && $decoded['evt'] !== null) {
                        // Example: READY comes as cmd=DISPATCH, evt=READY
                        $this->emit('dispatch', [$decoded['evt'], $decoded['data'] ?? null, $decoded]);
                        if ($decoded['evt'] === 'READY') {
                            $this->ready = true;
                            // flush pending commands
                            while (! empty($this->pendingCommands)) {
                                $this->writeFrame(1, array_shift($this->pendingCommands));
                            }
                            $this->emit('ready', [$decoded['data'] ?? null, $decoded]);
                        }
                        if (isset($decoded['evt']) && $decoded['evt'] === 'ERROR') {
                            $this->emit('error', [$decoded]);
                        }
                    } else {
                        // Command response; includes 'cmd' and optionally 'nonce' and 'data'
                        if (isset($decoded['cmd'])) {
                            $this->emit('response', [$decoded['cmd'], $decoded['data'] ?? null, $decoded['nonce'] ?? null, $decoded]);
                        }
                    }
                }
            } elseif ($opcode === 2) {
                // CLOSE: remote closed the connection intent
                $this->emit('close');
                $this->close();
            } elseif ($opcode === 3) {
                // PING: respond with PONG (opcode 4) echoing payload
                try {
                    $this->writeFrame(4, $payload);
                } catch (\Throwable $e) {
                    $this->emit('error', [$e]);
                }
            } elseif ($opcode === 4) {
                // PONG: ignore or emit ping/pong event
                $this->emit('pong', [$payload]);
            }

            // Yield to the event loop before processing the next frame to keep responsiveness.
            return $this->yieldTick()->then(fn () => $this->processBufferAsync());
        });
    }

    /**
     * Yield to the event loop on the next tick. Useful for breaking up long processing loops to keep the event loop responsive.
     *
     * @return PromiseInterface
     */
    protected function yieldTick(): PromiseInterface
    {
        $d = new Deferred();

        $this->loop->futureTick(fn () => $d->resolve(null));

        return $d->promise();
    }

    /**
     * Write a frame to the IPC connection with the given opcode and payload. The payload will be prefixed with its length as required by the protocol.
     *
     * @param int    $opcode  The opcode of the frame.
     * @param string $payload The payload of the frame.
     *
     * @throws \RuntimeException if not connected.
     */
    protected function writeFrame(int $opcode, string $payload): void
    {
        if ($this->conn === null) {
            throw new \RuntimeException('Not connected');
        }

        $data = pack('V', $opcode).pack('V', strlen($payload)).$payload;
        // Debug: print outgoing frame header and payload summary
        try {
            echo "[IpcClient] -> opcode={$opcode} len=" . strlen($payload) . " payload=" . substr($payload, 0, 200) . PHP_EOL;
        } catch (\Throwable $e) {
        }
        if (is_object($this->conn) && method_exists($this->conn, 'write')) {
            $this->conn->write($data);
            return;
        }

        throw new \RuntimeException('Connected stream does not support write()');
    }

    /**
     * Send an RPC command. Non-blocking — frame is written to socket.
     *
     * @param string      $cmd   The command name (e.g. "GET_GUILDS").
     * @param array       $args  Optional command arguments.
     * @param string|null $evt   Optional event name to emit when response is received.
     * @param string|null $nonce Optional nonce to correlate responses.
     *
     * @throws \RuntimeException if not connected or if payload encoding fails.
     */
    public function sendCommand(string $cmd, array $args = [], ?string $evt = null, ?string $nonce = null): void
    {
        $payload = ['cmd' => $cmd];

        if ($args !== []) {
            $payload['args'] = $args;
        }
        if ($evt !== null) {
            $payload['evt'] = $evt;
        }
        if ($nonce !== null) {
            $payload['nonce'] = $nonce;
        }

        $json = json_encode($payload);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode command payload as JSON');
        }

        if ($this->ready) {
            $this->writeFrame(1, $json);
        } else {
            // queue until READY
            $this->pendingCommands[] = $json;
        }
    }

    /**
     * Send an AUTHORIZE command and wait for the server response.
     * Resolves with the decoded response payload (raw array) when received.
     *
     * @param array $args Arguments for AUTHORIZE (e.g. ['client_id' => '...', 'scopes' => ['rpc','identify']])
     * @param float $timeout Seconds to wait for a response before rejecting (default 15s)
     * 
     * @return PromiseInterface Resolves with decoded response array on success, rejects on timeout or error
     */
    public function authorize(array $args, float $timeout = 15.0): PromiseInterface
    {
        $nonce = bin2hex(random_bytes(12));
        $d = new Deferred();

        $loop = $this->loop;

        // Send the AUTHORIZE command (will be queued until READY if necessary)
        try {
            $this->sendCommand('AUTHORIZE', $args, null, $nonce);
        } catch (\Throwable $e) {
            $d->reject($e);
            return $d->promise();
        }

        // Timeout
        $timer = $loop->addTimer($timeout, function () use ($nonce, $d) {
            if (isset($this->pendingAuthorizations[$nonce])) {
                unset($this->pendingAuthorizations[$nonce]);
                $d->reject(new \RuntimeException('Timeout waiting for AUTHORIZE response'));
            }
        });

        // Store pending authorization so the internal response handler can resolve it.
        $this->pendingAuthorizations[$nonce] = ['deferred' => $d, 'timer' => $timer];

        // Ensure timer cleanup on resolution/rejection
        $d->promise()->then(function ($v) use ($loop, $timer) {
            try { $loop->cancelTimer($timer); } catch (\Throwable $e) {}
        }, function ($e) use ($loop, $timer) {
            try { $loop->cancelTimer($timer); } catch (\Throwable $err) {}
        });

        return $d->promise();
    }

    /**
     * Send a PING frame. The payload will be echoed in the PONG by the server.
     */
    public function sendPing(string $payload = ''): void
    {
        $this->writeFrame(3, $payload);
    }

    /**
     * Send a CLOSE frame and close the connection.
     */
    public function sendClose(string $reason = ''): void
    {
        try {
            $this->writeFrame(2, $reason);
        } finally {
            $this->close();
        }
    }

    /**
     * Convenience helper: run AUTHORIZE, exchange the returned code for an OAuth token,
     * then call AUTHENTICATE with the returned access token. Returns the AUTHENTICATE response.
     *
     * Note: This helper performs a blocking HTTP POST to Discord's token endpoint. If you
     * require non-blocking HTTP, replace with a ReactPHP HTTP client.
     *
     * @param array $authorizeArgs Arguments for AUTHORIZE (e.g. ['client_id' => '...', 'scopes' => ['rpc','identify']])
     * @param string $clientSecret OAuth2 client secret for token exchange
     * @param float $authorizeTimeout Timeout for AUTHORIZE reply
     * @param float $httpTimeout Timeout for token HTTP request
     * 
     * @return PromiseInterface Resolves with AUTHENTICATE response raw array
     */
    public function authorizeAndAuthenticate(array $authorizeArgs, string $clientSecret, float $authorizeTimeout = 15.0, float $httpTimeout = 15.0): PromiseInterface
    {
        $deferred = new Deferred();

        $this->authorize($authorizeArgs, $authorizeTimeout)->then(function ($authorizeRaw) use ($clientSecret, $httpTimeout, $deferred, $authorizeTimeout) {
            $code = $authorizeRaw['data']['code'] ?? null;
            if ($code === null) {
                $deferred->reject(new \RuntimeException('AUTHORIZE did not return a code'));
                return;
            }

            // Exchange code for token (blocking HTTP call)
            $post = http_build_query([
                'client_id' => $this->connectClientId ?? ($authorizeArgs['client_id'] ?? ''),
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $authorizeArgs['redirect_uri'] ?? 'http://localhost'
            ]);

            $opts = ['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $post,
                'timeout' => $httpTimeout
            ]];

            $context = stream_context_create($opts);
            $resp = @file_get_contents('https://discord.com/api/oauth2/token', false, $context);
            if ($resp === false) {
                $deferred->reject(new \RuntimeException('Token exchange HTTP request failed'));
                return;
            }

            $json = json_decode($resp, true);
            if ($json === null || !isset($json['access_token'])) {
                $deferred->reject(new \RuntimeException('Token exchange failed or returned invalid response'));
                return;
            }

            $accessToken = $json['access_token'];

            // Send AUTHENTICATE command and wait for response; use a nonce and pendingAuthorizations map
            $nonce = bin2hex(random_bytes(12));
            $authDeferred = new Deferred();

            // Timeout for AUTHENTICATE response
            $authTimeout = $authorizeTimeout;
            $timer = $this->loop->addTimer($authTimeout, function () use ($nonce, $authDeferred) {
                if (isset($this->pendingAuthorizations[$nonce])) {
                    unset($this->pendingAuthorizations[$nonce]);
                    $authDeferred->reject(new \RuntimeException('Timeout waiting for AUTHENTICATE response'));
                }
            });

            $this->pendingAuthorizations[$nonce] = ['deferred' => $authDeferred, 'timer' => $timer];

            try {
                $this->sendCommand('AUTHENTICATE', ['access_token' => $accessToken], null, $nonce);
            } catch (\Throwable $e) {
                unset($this->pendingAuthorizations[$nonce]);
                try { $this->loop->cancelTimer($timer); } catch (\Throwable $err) {}
                $deferred->reject($e);
                return;
            }

            $authDeferred->promise()->then(function ($raw) use ($deferred, $timer) {
                try { $this->loop->cancelTimer($timer); } catch (\Throwable $e) {}
                $deferred->resolve($raw);
            }, function ($e) use ($deferred, $timer) {
                try { $this->loop->cancelTimer($timer); } catch (\Throwable $err) {}
                $deferred->reject($e);
            });
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Internal handler for 'response' events. Resolves any pending authorizations keyed by nonce.
     *
     * Emitted signature: ($cmd, $data, $nonce, $raw)
     */
    protected function handleResponseEvent(string $cmd, $data, ?string $nonce, $raw): void
    {
        if ($nonce === null) {
            return;
        }

        if (! isset($this->pendingAuthorizations[$nonce])) {
            return;
        }

        $entry = $this->pendingAuthorizations[$nonce];
        unset($this->pendingAuthorizations[$nonce]);

        $d = $entry['deferred'];
        $timer = $entry['timer'] ?? null;

        try {
            if ($timer !== null) {
                try { $this->loop->cancelTimer($timer); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
        }

        try {
            $d->resolve($raw);
        } catch (\Throwable $e) {
            // ensure we don't throw from internal handler
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}

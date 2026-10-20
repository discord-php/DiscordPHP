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
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;

use function React\Promise\reject;

/**
 * Optional local RPC transport, independent of the bot Gateway runtime.
 *
 * connect() resolves only after READY. request() is a low-level raw-payload API,
 * not a typed implementation of every RPC command. Events: ready(data, raw),
 * dispatch(event, data, raw), error(Throwable), close(?CloseException), pong(bytes).
 * No payload, OAuth code, or token is printed or logged by this module.
 *
 * @link https://docs.discord.com/developers/topics/rpc
 */
final class IpcClient extends EventEmitter
{
    private LoopInterface $loop;
    private ?ConnectorInterface $connector;
    private FrameDecoder $decoder;
    private array $options;
    private string $state = 'disconnected';
    private string $clientId = '';
    private int $generation = 0;
    private ?ConnectionInterface $connection = null;
    private ?Deferred $connecting = null;
    private ?PromiseInterface $attempt = null;
    private ?TimerInterface $connectTimer = null;
    private array $streamListeners = [];
    /** @var array<string, array{deferred: Deferred, timer: TimerInterface, command: string, event: ?string}> */
    private array $pending = [];
    /** @var array<string, array{bytes: string, nonce: ?string}> */
    private array $outbound = [];
    private int $queueBytes = 0;
    private int $controlSequence = 0;
    private bool $blocked = false;
    private bool $reading = false;
    private bool $writing = false;
    private bool $pumping = false;
    private bool $authenticating = false;

    /**
     * options: paths, connect_timeout, max_frame_bytes, max_buffer_bytes,
     * max_pending_requests, max_queue_bytes, frames_per_tick.
     * Windows requires an injected native asynchronous pipe connector.
     */
    public function __construct(?LoopInterface $loop = null, ?ConnectorInterface $connector = null, array $options = [])
    {
        $defaults = [
            'paths' => self::getCandidatePaths(),
            'connect_timeout' => 10.0,
            'max_frame_bytes' => 1048576,
            'max_buffer_bytes' => 2097168,
            'max_pending_requests' => 128,
            'max_queue_bytes' => 4194304,
            'frames_per_tick' => 32,
        ];
        if (array_diff_key($options, $defaults)) {
            throw new \InvalidArgumentException('Unknown IPC option.');
        }
        $this->options = $options + $defaults;
        foreach (['max_frame_bytes', 'max_buffer_bytes', 'max_pending_requests', 'max_queue_bytes', 'frames_per_tick'] as $key) {
            if (! is_int($this->options[$key]) || $this->options[$key] < 1 || $this->options[$key] > 67108864) {
                throw new \InvalidArgumentException('Invalid IPC resource limit.');
            }
        }
        if ($this->options['max_pending_requests'] > 10000 || $this->options['frames_per_tick'] > 1024 || ! is_numeric($this->options['connect_timeout']) || ! is_finite((float) $this->options['connect_timeout']) || $this->options['connect_timeout'] <= 0) {
            throw new \InvalidArgumentException('Invalid IPC scheduling limit.');
        }
        if (! is_array($this->options['paths']) || ! array_is_list($this->options['paths']) || ! $this->options['paths'] || count($this->options['paths']) > 50) {
            throw new \InvalidArgumentException('Provide between one and fifty local IPC paths.');
        }
        foreach ($this->options['paths'] as $path) {
            if (! is_string($path) || str_contains($path, "\0") || ! (str_starts_with($path, 'unix:///') || preg_match('~^\\\\\\\\[?.]\\\\pipe\\\\discord-ipc-[0-9]$~D', $path))) {
                throw new \InvalidArgumentException('Only local Unix sockets or Discord named pipes are accepted.');
            }
        }
        $this->loop = $loop ?? Loop::get();
        $this->connector = $connector ?? (PHP_OS_FAMILY === 'Windows' ? null : new Connector(['tcp' => false, 'tls' => false, 'dns' => false], $this->loop));
        $this->decoder = new FrameDecoder($this->options['max_frame_bytes'], $this->options['max_buffer_bytes']);
    }

    /** @return string[] Local URIs in documented environment-prefix/index order. */
    public static function getCandidatePaths(?array $environment = null, ?bool $windows = null): array
    {
        if ($windows ?? PHP_OS_FAMILY === 'Windows') {
            return array_map(static fn ($index) => '\\\\?\\pipe\\discord-ipc-'.$index, range(0, 9));
        }
        $directories = [];
        foreach (['XDG_RUNTIME_DIR', 'TMPDIR', 'TMP', 'TEMP'] as $name) {
            $value = $environment === null ? getenv($name) : ($environment[$name] ?? false);
            if (is_string($value) && str_starts_with($value, '/') && ! str_contains($value, "\0")) {
                $directories[] = rtrim($value, '/');
            }
        }
        $directories[] = '/tmp';
        $paths = [];
        foreach (array_unique($directories) as $directory) {
            foreach (range(0, 9) as $index) {
                $paths[] = 'unix://'.$directory.'/discord-ipc-'.$index;
            }
        }

        return $paths;
    }

    /** @return PromiseInterface<self> READY, not just a successful socket connection. */
    public function connect(string $clientId, int $version = 1): PromiseInterface
    {
        if ($this->state !== 'disconnected') {
            return reject(new \LogicException('IPC client is already connecting or connected.'));
        }
        if (! preg_match('/^[0-9]+$/D', $clientId) || $version !== 1) {
            return reject(new \InvalidArgumentException('A numeric client ID and IPC version 1 are required.'));
        }
        if ($this->connector === null) {
            return reject(new \RuntimeException('Windows IPC requires an injected asynchronous native pipe connector.'));
        }
        $generation = ++$this->generation;
        $this->clientId = $clientId;
        $this->state = 'connecting';
        $this->connecting = new Deferred(function () use ($generation) {
            if ($generation === $this->generation) {
                $this->disconnect(new \RuntimeException('IPC connection cancelled.'));
            }
        });
        $promise = $this->connecting->promise();
        $this->connectTimer = $this->loop->addTimer((float) $this->options['connect_timeout'], function () use ($generation) {
            if ($generation === $this->generation) {
                $this->disconnect(new \RuntimeException('IPC READY deadline exceeded.'));
            }
        });
        $this->loop->futureTick(fn () => $this->tryPath($generation, 0));

        return $promise;
    }

    private function tryPath(int $generation, int $index): void
    {
        if ($generation !== $this->generation || $this->state !== 'connecting') {
            return;
        }
        if (! isset($this->options['paths'][$index])) {
            $this->disconnect(new \RuntimeException('No local Discord IPC socket is available.'));

            return;
        }
        try {
            $attempt = $this->connector->connect($this->options['paths'][$index]);
            $this->attempt = $attempt;
            $attempt->then(function (ConnectionInterface $connection) use ($generation) {
                if ($generation !== $this->generation || $this->state !== 'connecting') {
                    $connection->close();

                    return;
                }
                try {
                    $this->attempt = null;
                    $this->connection = $connection;
                    $this->state = 'handshaking';
                    $this->attach($connection, $generation);
                    $this->enqueueControl(0, json_encode(['v' => 1, 'client_id' => $this->clientId], JSON_THROW_ON_ERROR));
                } catch (\Throwable $error) {
                    $this->disconnect(new \RuntimeException('IPC connection setup failed.'));
                }
            }, function () use ($generation, $index) {
                if ($generation === $this->generation) {
                    $this->attempt = null;
                    $this->loop->futureTick(fn () => $this->tryPath($generation, $index + 1));
                }
            });
        } catch (\Throwable $error) {
            $this->disconnect(new \RuntimeException('IPC connection setup failed.'));
        }
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
                    $this->disconnect($error);
                }
            },
            'drain' => function () use ($generation) {
                if ($generation === $this->generation) {
                    $this->blocked = false;
                    $this->pump();
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
        $this->loop->futureTick(function () use ($generation) {
            if ($generation !== $this->generation) {
                return;
            }
            $this->reading = false;
            try {
                for ($count = 0; $count < $this->options['frames_per_tick']; $count++) {
                    $frame = $this->decoder->next();
                    if ($frame === null) {
                        return;
                    }
                    $this->receive($frame[0], $frame[1]);
                    if ($generation !== $this->generation) {
                        return;
                    }
                }
                $this->scheduleRead($generation);
            } catch (ProtocolException $error) {
                $this->disconnect($error);
            }
        });
    }

    private function receive(int $opcode, string $payload): void
    {
        if ($opcode === 3) {
            $this->enqueueControl(4, $payload);

            return;
        }
        if ($opcode === 4) {
            $this->emit('pong', [$payload]);

            return;
        }
        try {
            $object = json_decode($payload, false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new ProtocolException('Invalid IPC JSON.');
        }
        if (! $object instanceof \stdClass) {
            throw new ProtocolException('IPC payload must be an object.');
        }
        $raw = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
        if ($opcode === 2) {
            if (! isset($raw['code'], $raw['message']) || ! is_int($raw['code']) || ! is_string($raw['message'])) {
                throw new ProtocolException('Invalid IPC CLOSE payload.');
            }
            $this->disconnect(new CloseException($raw['code'], $raw['message']));

            return;
        }
        if (! isset($raw['cmd']) || ! is_string($raw['cmd']) || ! preg_match('/^[A-Z_]+$/D', $raw['cmd']) || (isset($raw['evt']) && ! is_string($raw['evt'])) || (isset($raw['nonce']) && ! is_string($raw['nonce']))) {
            throw new ProtocolException('Invalid RPC envelope.');
        }
        $nonce = $raw['nonce'] ?? null;
        $event = $raw['evt'] ?? null;
        if ($nonce !== null) {
            if (! isset($this->pending[$nonce])) {
                return; // A late or cancelled response must never become a notification.
            }
            $entry = $this->pending[$nonce];
            if ($raw['cmd'] !== $entry['command'] || ($event !== 'ERROR' && $event !== $entry['event'])) {
                $this->finish($nonce, null, new ProtocolException('RPC response does not match its request.'));

                return;
            }
            if ($event === 'ERROR') {
                $code = $raw['data']['code'] ?? null;
                if (! is_int($code)) {
                    throw new ProtocolException('Invalid RPC command error.');
                }
                $this->finish($nonce, null, new CommandException($entry['command'], $code));
            } else {
                $this->finish($nonce, $raw);
            }

            return;
        }
        if ($raw['cmd'] !== 'DISPATCH' || $event === null) {
            throw new ProtocolException('Uncorrelated RPC response.');
        }
        if ($event === 'ERROR') {
            $this->disconnect(new ProtocolException('Uncorrelated RPC server error.'));

            return;
        }
        if ($event === 'READY') {
            if ($this->state !== 'handshaking') {
                throw new ProtocolException('Unexpected RPC READY.');
            }
            $this->state = 'ready';
            $connecting = $this->connecting;
            $this->connecting = null;
            $this->loop->cancelTimer($this->connectTimer);
            $this->connectTimer = null;
            $generation = $this->generation;
            $connecting->resolve($this);
            if ($this->generation === $generation && $this->state === 'ready') {
                $this->pump();
                $this->emit('ready', [$raw['data'] ?? null, $raw]);
            }

            return;
        }
        if ($this->state !== 'ready') {
            throw new ProtocolException('RPC event received before READY.');
        }
        $this->emit('dispatch', [$event, $raw['data'] ?? null, $raw]);
    }

    /**
     * Raw RPC command escape hatch; callers own command-specific scopes, arguments and result shapes.
     * Deadlines include queue time. Cancellation removes unsent frames; sent commands cannot be undone.
     *
     * @return PromiseInterface<array> Complete correlated response envelope.
     */
    public function request(string $command, array $args = [], ?string $event = null, float $timeout = 15.0): PromiseInterface
    {
        if ($this->state === 'disconnected' || $this->state === 'closing') {
            return reject(new \RuntimeException('IPC is disconnected.'));
        }
        if (! preg_match('/^[A-Z_]+$/D', $command) || $command === 'DISPATCH' || ($event !== null && ! preg_match('/^[A-Z_]+$/D', $event)) || ! is_finite($timeout) || $timeout <= 0) {
            return reject(new \InvalidArgumentException('Invalid RPC command, event or timeout.'));
        }
        if (count($this->pending) >= $this->options['max_pending_requests']) {
            return reject(new \OverflowException('RPC pending request limit exceeded.'));
        }
        $nonce = bin2hex(random_bytes(16));
        try {
            $payload = json_encode(['cmd' => $command, 'args' => (object) $args, 'evt' => $event, 'nonce' => $nonce], JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            return reject(new \InvalidArgumentException('RPC arguments cannot be encoded.'));
        }
        if (strlen($payload) > $this->options['max_frame_bytes']) {
            return reject(new \OverflowException('RPC command frame limit exceeded.'));
        }
        $deferred = new Deferred(fn () => $this->finish($nonce, null, new \RuntimeException('RPC request cancelled.')));
        $timer = $this->loop->addTimer($timeout, fn () => $this->finish($nonce, null, new \RuntimeException('RPC request deadline exceeded.')));
        $this->pending[$nonce] = ['deferred' => $deferred, 'timer' => $timer, 'command' => $command, 'event' => $event];
        try {
            $this->enqueue($nonce, FrameDecoder::encode(1, $payload), $nonce);
        } catch (\OverflowException $error) {
            $this->finish($nonce, null, $error);
        }

        return $deferred->promise();
    }

    private function enqueueControl(int $opcode, string $payload): void
    {
        try {
            $this->enqueue('control:'.++$this->controlSequence, FrameDecoder::encode($opcode, $payload), null);
        } catch (\OverflowException $error) {
            $this->disconnect($error);
        }
    }

    private function enqueue(string $key, string $bytes, ?string $nonce): void
    {
        if (strlen($bytes) > $this->options['max_queue_bytes'] - $this->queueBytes || count($this->outbound) >= $this->options['max_pending_requests'] + 32) {
            throw new \OverflowException('IPC outbound queue limit exceeded.');
        }
        $this->outbound[$key] = ['bytes' => $bytes, 'nonce' => $nonce];
        $this->queueBytes += strlen($bytes);
        $this->pump();
    }

    private function pump(): void
    {
        if ($this->connection === null || $this->blocked || $this->pumping) {
            return;
        }
        $this->pumping = true;
        $generation = $this->generation;
        $count = 0;
        try {
            foreach ($this->outbound as $key => $entry) {
                if (! isset($this->outbound[$key])) {
                    continue;
                }
                if ($entry['nonce'] !== null && $this->state !== 'ready') {
                    continue;
                }
                unset($this->outbound[$key]);
                $this->queueBytes -= strlen($entry['bytes']);
                // React write(false) means accepted but backpressured, not a partial/rejected frame.
                $accepted = $this->connection->write($entry['bytes']);
                if ($generation !== $this->generation) {
                    break;
                }
                $this->blocked = ! $accepted;
                if ($this->blocked || $this->connection === null || ++$count >= $this->options['frames_per_tick']) {
                    break;
                }
            }
        } catch (\Throwable $error) {
            $this->disconnect(new \RuntimeException('IPC write failed.'));
        } finally {
            $this->pumping = false;
        }
        if ($count >= $this->options['frames_per_tick'] && ! $this->writing) {
            $this->writing = true;
            $generation = $this->generation;
            $this->loop->futureTick(function () use ($generation) {
                if ($generation === $this->generation) {
                    $this->writing = false;
                    $this->pump();
                }
            });
        }
    }

    private function finish(string $nonce, ?array $response, ?\Throwable $error = null): void
    {
        if (! isset($this->pending[$nonce])) {
            return;
        }
        $entry = $this->pending[$nonce];
        unset($this->pending[$nonce]);
        $this->loop->cancelTimer($entry['timer']);
        if (isset($this->outbound[$nonce])) {
            $this->queueBytes -= strlen($this->outbound[$nonce]['bytes']);
            unset($this->outbound[$nonce]);
        }
        if ($error !== null) {
            $entry['deferred']->reject($error);
        } else {
            $entry['deferred']->resolve($response);
        }
    }

    public function authorize(array $scopes = ['rpc', 'identify'], float $timeout = 15.0): PromiseInterface
    {
        if (! array_is_list($scopes) || ! $scopes || array_filter($scopes, static fn ($scope) => ! is_string($scope) || $scope === '')) {
            return reject(new \InvalidArgumentException('OAuth scopes must be a nonempty list of strings.'));
        }

        return $this->request('AUTHORIZE', ['client_id' => $this->clientId, 'scopes' => $scopes], null, $timeout);
    }

    public function authenticate(string $accessToken, float $timeout = 15.0): PromiseInterface
    {
        if ($accessToken === '') {
            return reject(new \InvalidArgumentException('An access token is required.'));
        }

        return $this->request('AUTHENTICATE', ['access_token' => $accessToken], null, $timeout);
    }

    /**
     * Preserve this connection's client identity and exact redirect URI across an injected async exchange.
     * No token is retained after the AUTHENTICATE request settles. OAuth failures are sanitized.
     *
     * @return PromiseInterface<array> AUTHENTICATE response envelope.
     */
    public function authorizeAndAuthenticate(array $scopes, string $redirectUri, TokenExchangeInterface $exchange, float $timeout = 15.0): PromiseInterface
    {
        if ($this->state === 'disconnected' || $this->state === 'closing' || $redirectUri === '' || ! is_finite($timeout) || $timeout <= 0) {
            return reject(new \InvalidArgumentException('An active connection, redirect URI and positive timeout are required.'));
        }
        if ($this->authenticating) {
            return reject(new \LogicException('An RPC authentication flow is already active.'));
        }
        $this->authenticating = true;
        $generation = $this->generation;
        $clientId = $this->clientId;
        $operation = null;
        $settled = false;
        $complete = null;
        $deferred = new Deferred(function () use (&$operation, &$settled, &$complete) {
            if (! $settled) {
                $complete(null, new \RuntimeException('RPC authentication cancelled.'));
                $operation?->cancel();
            }
        });
        $complete = function (?array $value, ?\Throwable $error = null) use ($deferred, &$settled) {
            if ($settled) {
                return;
            }
            $settled = true;
            if ($error !== null) {
                $deferred->reject($error);
            } else {
                $deferred->resolve($value);
            }
        };
        $timer = $this->loop->addTimer($timeout, function () use (&$operation, $complete) {
            $complete(null, new \RuntimeException('RPC authentication deadline exceeded.'));
            $operation?->cancel();
        });
        $onClose = function () use (&$operation, $complete) {
            $complete(null, new \RuntimeException('IPC closed during authentication.'));
            $operation?->cancel();
        };
        $this->on('close', $onClose);
        $cleanup = function () use ($timer, $onClose) {
            $this->authenticating = false;
            $this->loop->cancelTimer($timer);
            $this->removeListener('close', $onClose);
        };
        $deferred->promise()->then($cleanup, $cleanup);
        $operation = $this->authorize($scopes, $timeout);
        $operation->then(function (array $raw) use ($exchange, $clientId, $redirectUri, &$operation, &$settled, $generation, $timeout, $complete) {
            if ($settled || $generation !== $this->generation) {
                return;
            }
            $code = $raw['data']['code'] ?? null;
            if (! is_string($code) || $code === '') {
                $complete(null, new ProtocolException('AUTHORIZE returned no code.'));

                return;
            }
            try {
                $operation = $exchange->exchange($code, $clientId, $redirectUri);
                $operation->then(function ($token) use (&$operation, &$settled, $generation, $timeout, $complete) {
                    if ($settled || $generation !== $this->generation) {
                        return;
                    }
                    if (! is_string($token) || $token === '') {
                        $complete(null, new ProtocolException('OAuth exchange returned no access token.'));

                        return;
                    }
                    $operation = $this->authenticate($token, $timeout);
                    $operation->then(fn ($raw) => $complete($raw), fn ($error) => $complete(null, $error));
                }, fn () => $complete(null, new \RuntimeException('RPC OAuth exchange failed.')));
            } catch (\Throwable $error) {
                $complete(null, new \RuntimeException('RPC OAuth exchange failed.'));
            }
        }, fn ($error) => $complete(null, $error));

        return $deferred->promise();
    }

    public function sendPing(string $payload = ''): void
    {
        if ($this->connection === null || strlen($payload) > $this->options['max_frame_bytes']) {
            throw new \LogicException('PING requires a connection and a bounded payload.');
        }
        $this->enqueueControl(3, $payload);
    }

    /** Explicit close; reconnect requires a new connect() call. No automatic replay or retry. */
    public function close(): void
    {
        $this->disconnect(new \RuntimeException('IPC client closed.'));
    }

    private function disconnect(\Throwable $error): void
    {
        if ($this->state === 'disconnected' || $this->state === 'closing') {
            return;
        }
        ++$this->generation;
        // Reject old operations before allowing callbacks to start another generation.
        $this->state = 'closing';
        $this->clientId = '';
        $connection = $this->connection;
        $this->connection = null;
        foreach ($this->streamListeners as $event => $listener) {
            $connection?->removeListener($event, $listener);
        }
        $this->streamListeners = [];
        $attempt = $this->attempt;
        $this->attempt = null;
        $connecting = $this->connecting;
        $this->connecting = null;
        if ($this->connectTimer !== null) {
            $this->loop->cancelTimer($this->connectTimer);
            $this->connectTimer = null;
        }
        $this->outbound = [];
        $this->queueBytes = 0;
        $this->blocked = $this->reading = $this->writing = false;
        $this->decoder->clear();
        $attempt?->cancel();
        $connection?->close();
        $connecting?->reject($error);
        foreach (array_keys($this->pending) as $nonce) {
            $this->finish($nonce, null, $error);
        }
        $this->state = 'disconnected';
        $this->emit('close', [$error instanceof CloseException ? $error : null]);
        if ($error instanceof ProtocolException) {
            $this->emit('error', [$error]);
        }
    }
}

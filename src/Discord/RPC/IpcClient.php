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
use React\Socket\ConnectorInterface;

use function React\Promise\reject;

/**
 * Optional local RPC facade, independent of the bot Gateway runtime.
 *
 * connect() resolves only after READY. request() provides raw command envelopes,
 * not typed support for every Discord RPC command. No payloads or tokens are logged.
 * Events: ready(data, raw), dispatch(event, data, raw), error(Throwable),
 * close(?CloseException), pong(bytes). Call close() to release owned resources.
 *
 * @link https://docs.discord.com/developers/topics/rpc
 * @SuppressWarnings(PHPMD.StaticAccess) Optional global-loop default and stateless wire/path utilities.
 * @SuppressWarnings(PHPMD.TooManyMethods) Public lifecycle/command facade over focused internal units.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Facade wires the focused transport, protocol and OAuth units.
 * @since 10.69.0
 */
final class IpcClient extends EventEmitter
{
    private LoopInterface $loop;
    private array $options;
    private IpcSession $session;
    private OutboundQueue $outbound;
    private PendingRequests $pending;
    private string $state = 'disconnected';
    private string $clientId = '';
    private int $generation = 0;
    private ?Deferred $connecting = null;
    private ?TimerInterface $connectTimer = null;
    private ?OAuthFlow $authentication = null;

    /** Windows requires an injected asynchronous connector, such as WindowsPipeConnector. See guide/rpc.rst for options. */
    public function __construct(?LoopInterface $loop = null, ?ConnectorInterface $connector = null, array $options = [])
    {
        $this->options = (new IpcOptions($options))->values();
        $this->loop = $loop ?? Loop::get();
        $this->session = new IpcSession($this->loop, $connector, $this->options);
        $this->outbound = new OutboundQueue($this->loop, $this->options);
        $this->pending = new PendingRequests($this->loop, $this->outbound, $this->options);
        $this->session->on('frame', function (int $opcode, string $payload) {
            try {
                $this->receive($opcode, $payload);
            } catch (ProtocolException $error) {
                $this->disconnect($error);
            }
        });
        $this->session->on('close', fn (\Throwable $error) => $this->disconnect($error));
        $this->outbound->on('error', fn (\Throwable $error) => $this->disconnect($error));
    }

    /** @return string[] Local paths in documented environment-prefix/index order. */
    public static function getCandidatePaths(?array $environment = null, ?bool $windows = null): array
    {
        return IpcOptions::candidatePaths($environment, $windows);
    }

    /** @return PromiseInterface<self> READY, rather than only an established socket. */
    public function connect(string $clientId, int $version = 1): PromiseInterface
    {
        if ($this->state !== 'disconnected') {
            return reject(new \LogicException('IPC client is already connecting or connected.'));
        }
        if (! preg_match('/^[0-9]+$/D', $clientId) || $version !== 1) {
            return reject(new \InvalidArgumentException('A numeric client ID and IPC version 1 are required.'));
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
        $this->session->open()->then(fn (ConnectionInterface $connection) => $this->handshake($connection, $generation), function (\Throwable $error) use ($generation) {
            if ($generation === $this->generation) {
                $this->disconnect($error);
            }
        });

        return $promise;
    }

    private function handshake(ConnectionInterface $connection, int $generation): void
    {
        if ($generation !== $this->generation) {
            $connection->close();

            return;
        }
        try {
            $this->state = 'handshaking';
            $this->outbound->bind($connection);
            $this->outbound->control(0, json_encode(['v' => 1, 'client_id' => $this->clientId], JSON_THROW_ON_ERROR));
        } catch (\Throwable $error) {
            if ($generation === $this->generation) {
                $this->disconnect(new \RuntimeException('IPC connection setup failed.'));
            }
        }
    }

    private function receive(int $opcode, string $payload): void
    {
        if ($opcode === 3) {
            $this->outbound->control(4, $payload);

            return;
        }
        if ($opcode === 4) {
            $this->emit('pong', [$payload]);

            return;
        }
        $raw = RpcEnvelope::decode($opcode, $payload);
        if ($opcode === 2) {
            $this->disconnect(new CloseException($raw['code'], $raw['message']));

            return;
        }
        if (isset($raw['nonce'])) {
            $this->pending->receive($raw);

            return;
        }
        if ($raw['evt'] === 'READY') {
            $this->ready($raw);

            return;
        }
        if ($this->state !== 'ready') {
            throw new ProtocolException('RPC event received before READY.');
        }
        $this->emit('dispatch', [$raw['evt'], $raw['data'] ?? null, $raw]);
    }

    private function ready(array $raw): void
    {
        if ($this->state !== 'handshaking') {
            throw new ProtocolException('Unexpected RPC READY.');
        }
        $this->state = 'ready';
        $generation = $this->generation;
        $connecting = $this->connecting;
        $this->connecting = null;
        $this->loop->cancelTimer($this->connectTimer);
        $this->connectTimer = null;
        $connecting->resolve($this);
        if ($generation !== $this->generation) {
            return;
        }
        $this->outbound->activate();
        if ($generation === $this->generation) {
            $this->emit('ready', [$raw['data'] ?? null, $raw]);
        }
    }

    /**
     * Raw command API. Callers own command-specific arguments, scopes and result shapes.
     * Deadlines include queue time; cancelling a sent command cannot undo its effect.
     *
     * @return PromiseInterface<array> Complete correlated response envelope.
     */
    public function request(string $command, array $args = [], ?string $event = null, float $timeout = 15.0): PromiseInterface
    {
        if (in_array($this->state, ['disconnected', 'closing'], true)) {
            return reject(new \RuntimeException('IPC is disconnected.'));
        }

        return $this->pending->request($command, $args, $event, $timeout);
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

    /** @return PromiseInterface<array> AUTHENTICATE envelope after an injected async exchange. */
    public function authorizeAndAuthenticate(array $scopes, string $redirectUri, TokenExchangeInterface $exchange, float $timeout = 15.0): PromiseInterface
    {
        if (in_array($this->state, ['disconnected', 'closing'], true)) {
            return reject(new \LogicException('RPC authentication requires an active connection.'));
        }
        if ($this->authentication !== null && ! $this->authentication->settled()) {
            return reject(new \LogicException('An RPC authentication flow is already active.'));
        }
        $flow = new OAuthFlow($this, $this->loop, $this->clientId);
        try {
            $promise = $flow->start($scopes, $redirectUri, $exchange, $timeout);
        } catch (\InvalidArgumentException $error) {
            return reject($error);
        }
        $this->authentication = $flow;
        $cleanup = function () use ($flow) {
            if ($this->authentication === $flow) {
                $this->authentication = null;
            }
        };
        $promise->then($cleanup, $cleanup);

        return $promise;
    }

    public function sendPing(string $payload = ''): void
    {
        if (! in_array($this->state, ['handshaking', 'ready'], true) || strlen($payload) > $this->options['max_frame_bytes']) {
            throw new \LogicException('PING requires a connection and a bounded payload.');
        }
        $this->outbound->control(3, $payload);
    }

    /** Explicit close; reconnect requires a new connect() call. No automatic replay or retry. */
    public function close(): void
    {
        $this->disconnect(new \RuntimeException('IPC client closed.'));
    }

    private function disconnect(\Throwable $error): void
    {
        if (in_array($this->state, ['disconnected', 'closing'], true)) {
            return;
        }
        ++$this->generation;
        $this->state = 'closing';
        $this->clientId = '';
        $connecting = $this->connecting;
        $this->connecting = null;
        $this->outbound->close();
        $this->session->close();
        if ($this->connectTimer !== null) {
            $this->loop->cancelTimer($this->connectTimer);
            $this->connectTimer = null;
        }
        $connecting?->reject($error);
        $this->pending->close($error);
        $this->state = 'disconnected';
        $this->emit('close', [$error instanceof CloseException ? $error : null]);
        if ($error instanceof ProtocolException) {
            $this->emit('error', [$error]);
        }
    }
}

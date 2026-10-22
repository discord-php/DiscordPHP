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
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * @internal Owns correlated requests and their deadlines; no command-specific domain modeling.
 * @SuppressWarnings(PHPMD.StaticAccess) FrameDecoder is a stateless wire codec.
 * @since 10.69.0
 */
final class PendingRequests
{
    private array $entries = [];
    private int $generation = 0;

    public function __construct(private LoopInterface $loop, private OutboundQueue $outbound, private array $options)
    {
    }

    public function request(string $command, array $args, ?string $event, float $timeout): PromiseInterface
    {
        $generation = $this->generation;
        try {
            $this->validate($command, $event, $timeout);
            $nonce = bin2hex(random_bytes(16));
            $bytes = $this->encode($command, $args, $event, $nonce);
            // JsonSerializable arguments can close/reconnect or recursively enqueue requests.
            if ($generation !== $this->generation) {
                return reject(new \RuntimeException('IPC closed while encoding RPC arguments.'));
            }
            $this->validate($command, $event, $timeout);
        } catch (\InvalidArgumentException|\OverflowException $error) {
            return reject($error);
        }
        $deferred = new Deferred(fn () => $this->finish($nonce, null, new \RuntimeException('RPC request cancelled.')));
        $timer = $this->loop->addTimer($timeout, fn () => $this->finish($nonce, null, new \RuntimeException('RPC request deadline exceeded.')));
        $this->entries[$nonce] = ['deferred' => $deferred, 'timer' => $timer, 'command' => $command, 'event' => $event];
        try {
            $this->outbound->send($nonce, $bytes);
        } catch (\OverflowException $error) {
            $this->finish($nonce, null, $error);
        }

        return $deferred->promise();
    }

    private function validate(string $command, ?string $event, float $timeout): void
    {
        if (! preg_match('/^[A-Z_]+$/D', $command) || $command === 'DISPATCH') {
            throw new \InvalidArgumentException('Invalid RPC command.');
        }
        if ($event !== null && ! preg_match('/^[A-Z_]+$/D', $event)) {
            throw new \InvalidArgumentException('Invalid RPC event.');
        }
        if (! is_finite($timeout) || $timeout <= 0) {
            throw new \InvalidArgumentException('Invalid RPC timeout.');
        }
        if (count($this->entries) >= $this->options['max_pending_requests']) {
            throw new \OverflowException('RPC pending request limit exceeded.');
        }
    }

    private function encode(string $command, array $args, ?string $event, string $nonce): string
    {
        try {
            $payload = json_encode(['cmd' => $command, 'args' => (object) $args, 'evt' => $event, 'nonce' => $nonce], JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            throw new \InvalidArgumentException('RPC arguments cannot be encoded.');
        }
        if (strlen($payload) > $this->options['max_frame_bytes']) {
            throw new \OverflowException('RPC command frame limit exceeded.');
        }

        return FrameDecoder::encode(1, $payload);
    }

    public function receive(array $raw): void
    {
        $nonce = $raw['nonce'];
        if (! isset($this->entries[$nonce])) {
            return; // Late/cancelled replies never become unsolicited dispatches.
        }
        $entry = $this->entries[$nonce];
        $event = $raw['evt'] ?? null;
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

            return;
        }
        $this->finish($nonce, $raw);
    }

    private function finish(string $nonce, ?array $response, ?\Throwable $error = null): void
    {
        if (! isset($this->entries[$nonce])) {
            return;
        }
        $entry = $this->entries[$nonce];
        unset($this->entries[$nonce]);
        $this->loop->cancelTimer($entry['timer']);
        $this->outbound->cancel($nonce);
        if ($error !== null) {
            $entry['deferred']->reject($error);

            return;
        }
        $entry['deferred']->resolve($response);
    }

    public function close(\Throwable $error): void
    {
        ++$this->generation;
        foreach (array_keys($this->entries) as $nonce) {
            $this->finish($nonce, null, $error);
        }
    }
}

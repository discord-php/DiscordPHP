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

/**
 * One session-bound OAuth operation with an overall deadline.
 *
 * @internal
 */
final class OAuthFlow
{
    private ?Deferred $deferred = null;
    private ?PromiseInterface $operation = null;
    private ?TimerInterface $timer = null;
    private ?\Closure $onClose = null;
    private bool $settled = false;
    private float $deadline = 0.0;

    public function __construct(private IpcClient $client, private LoopInterface $loop, private string $clientId)
    {
    }

    /** @return PromiseInterface<array> AUTHENTICATE response envelope. */
    public function start(array $scopes, string $redirectUri, TokenExchangeInterface $exchange, float $timeout): PromiseInterface
    {
        if ($this->deferred !== null) {
            throw new \LogicException('An OAuth flow can only be started once.');
        }
        if ($redirectUri === '' || ! is_finite($timeout) || $timeout <= 0) {
            throw new \InvalidArgumentException('A redirect URI and positive timeout are required.');
        }
        $this->deferred = new Deferred(fn () => $this->cancel());
        $this->deadline = hrtime(true) / 1e9 + $timeout;
        $this->onClose = fn () => $this->stop('IPC closed during authentication.');
        $this->client->on('close', $this->onClose);
        $this->timer = $this->loop->addTimer($timeout, fn () => $this->stop('RPC authentication deadline exceeded.'));
        $this->observe(
            $this->client->authorize($scopes, max(PHP_FLOAT_EPSILON, $this->deadline - hrtime(true) / 1e9)),
            fn (array $raw) => $this->exchange($raw, $redirectUri, $exchange),
            fn (\Throwable $error) => $this->finish(null, $error)
        );

        return $this->deferred->promise();
    }

    public function cancel(): void
    {
        $this->stop('RPC authentication cancelled.');
    }

    public function settled(): bool
    {
        return $this->settled;
    }

    private function exchange(array $raw, string $redirectUri, TokenExchangeInterface $exchange): void
    {
        if ($this->expired()) {
            return;
        }
        $code = $raw['data']['code'] ?? null;
        if (! is_string($code) || $code === '') {
            $this->finish(null, new ProtocolException('AUTHORIZE returned no code.'));

            return;
        }
        try {
            $operation = $exchange->exchange($code, $this->clientId, $redirectUri);
        } catch (\Throwable $error) {
            $this->finish(null, new \RuntimeException('RPC OAuth exchange failed.'));

            return;
        }
        $this->observe($operation, fn ($token) => $this->authenticate($token), fn () => $this->finish(null, new \RuntimeException('RPC OAuth exchange failed.')));
    }

    private function authenticate($token): void
    {
        if ($this->expired()) {
            return;
        }
        if (! is_string($token) || $token === '') {
            $this->finish(null, new ProtocolException('OAuth exchange returned no access token.'));

            return;
        }
        $this->observe(
            $this->client->authenticate($token, max(PHP_FLOAT_EPSILON, $this->deadline - hrtime(true) / 1e9)),
            function (array $raw) {
                if (! $this->expired()) {
                    $this->finish($raw);
                }
            },
            fn (\Throwable $error) => $this->finish(null, $error)
        );
    }

    /** Install handlers before cancelling a result returned after a synchronous close. */
    private function observe(PromiseInterface $operation, callable $resolve, callable $reject): void
    {
        if ($this->settled) {
            $operation->then(null, static function () {
            });
            $operation->cancel();

            return;
        }
        $this->operation = $operation;
        $operation->then($resolve, $reject);
    }

    /** Check elapsed time even if the loop has not dispatched its timer yet. */
    private function expired(): bool
    {
        if (! $this->settled && hrtime(true) / 1e9 >= $this->deadline) {
            $this->stop('RPC authentication deadline exceeded.');
        }

        return $this->settled;
    }

    private function stop(string $message): void
    {
        $operation = $this->operation;
        $this->finish(null, new \RuntimeException($message));
        $operation?->cancel();
    }

    private function finish(?array $value, ?\Throwable $error = null): void
    {
        if ($this->settled) {
            return;
        }
        $this->settled = true;
        $this->operation = null;
        if ($this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
        if ($this->onClose !== null) {
            $this->client->removeListener('close', $this->onClose);
            $this->onClose = null;
        }
        if ($error !== null) {
            $this->deferred?->reject($error);

            return;
        }
        $this->deferred?->resolve($value);
    }
}

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

namespace Discord\WebhookEvents;

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Op;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

/**
 * Receives the events Discord sends to the application's Webhook Events URL, and emits them on the client.
 *
 * Lobby messages, game direct messages and application authorizations only arrive this way. Each request's
 * signature is checked against the application's `verify_key`, acknowledged at once, and its event then runs
 * through the same handlers as gateway events, so it is listened for the same way:
 *
 * ```php
 * $discord->on(Event::LOBBY_MESSAGE_CREATE, fn (\Discord\Parts\Lobby\Message $message) => ...);
 * $discord->getWebhookEvents()->listen('127.0.0.1:8080');
 * ```
 *
 * Discord needs a public HTTPS URL, so put a reverse proxy in front of the address it listens on. The receiver
 * is also a ReactPHP request handler, so an application with its own `React\Http\HttpServer` can route to it instead.
 *
 * Get it from {@see Discord::getWebhookEvents()}.
 *
 * @link https://docs.discord.com/developers/events/webhook-events
 *
 * @since 10.59.0
 */
class WebhookEventReceiver
{
    /** A request Discord sends to check the URL is ready. */
    public const TYPE_PING = 0;

    /** A request carrying an event. */
    public const TYPE_EVENT = 1;

    /** The events Discord sends as webhooks. Any other type is acknowledged and dropped. */
    public const EVENTS = [
        Event::APPLICATION_AUTHORIZED,
        Event::APPLICATION_DEAUTHORIZED,
        Event::ENTITLEMENT_CREATE,
        Event::ENTITLEMENT_UPDATE,
        Event::ENTITLEMENT_DELETE,
        Event::LOBBY_MESSAGE_CREATE,
        Event::LOBBY_MESSAGE_UPDATE,
        Event::LOBBY_MESSAGE_DELETE,
        Event::GAME_DIRECT_MESSAGE_CREATE,
        Event::GAME_DIRECT_MESSAGE_UPDATE,
        Event::GAME_DIRECT_MESSAGE_DELETE,
    ];

    /** The events the gateway delivers as well. A webhook copy of one the gateway already delivered is dropped. */
    public const GATEWAY_EVENTS = [
        Event::ENTITLEMENT_CREATE,
        Event::ENTITLEMENT_UPDATE,
        Event::ENTITLEMENT_DELETE,
    ];

    protected Discord $discord;

    /**
     * Runs a packet through the client's dispatch handlers.
     *
     * @var \Closure(object): void
     */
    protected \Closure $dispatch;

    /**
     * The events already delivered, to drop copies of them.
     */
    protected DeliveredEvents $delivered;

    /**
     * The sockets opened by {@see WebhookEventReceiver::listen()}.
     *
     * @var SocketServer[]
     */
    protected array $sockets = [];

    /**
     * @internal Created by {@see Discord::getWebhookEvents()}.
     *
     * @param Discord  $discord  The client to emit events on.
     * @param callable $dispatch Runs a gateway-shaped packet through the client's dispatch handlers.
     */
    public function __construct(Discord $discord, callable $dispatch)
    {
        $this->discord = $discord;
        $this->dispatch = $dispatch(...);
        $this->delivered = new DeliveredEvents();

        foreach (self::GATEWAY_EVENTS as $event) {
            $discord->on($event, fn ($entitlement) => $this->delivered->remember(DeliveredEvents::entitlementKey($event, $entitlement)));
        }
    }

    /**
     * Receives webhook events on a socket.
     *
     * @param string $uri     The address to listen on, such as `127.0.0.1:8080`; see `React\Socket\SocketServer`.
     * @param array  $context Socket context options, such as `['tls' => [...]]` to serve HTTPS directly.
     *
     * @throws \RuntimeException The address could not be listened on, or signatures cannot be checked.
     *
     * @return SocketServer
     */
    public function listen(string $uri, array $context = []): SocketServer
    {
        Signature::assertAvailable();

        $socket = new SocketServer($uri, $context, $this->discord->getLoop());

        $server = new HttpServer($this->discord->getLoop(), $this);
        $server->on('error', fn (\Throwable $e) => $this->discord->getLogger()->error('webhook event server error', ['exception' => $e]));
        $server->listen($socket);

        $this->discord->getLogger()->info('receiving webhook events', ['address' => $socket->getAddress()]);

        return $this->sockets[] = $socket;
    }

    /**
     * Stops listening on every socket opened by {@see WebhookEventReceiver::listen()}.
     */
    public function close(): void
    {
        foreach ($this->sockets as $socket) {
            $socket->close();
        }

        $this->sockets = [];
    }

    /**
     * Handles one request from Discord.
     *
     * A signed request is acknowledged with `204` before its event is handled, since Discord only waits 3 seconds.
     *
     * @param ServerRequestInterface $request The request, with its body buffered.
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ('POST' !== $request->getMethod()) {
            return new Response(Response::STATUS_METHOD_NOT_ALLOWED, ['Allow' => 'POST']);
        }

        if (! $key = $this->discord->application?->verify_key) {
            $this->discord->getLogger()->warning('webhook event received before the application was loaded, so it cannot be verified');

            return new Response(Response::STATUS_SERVICE_UNAVAILABLE);
        }

        $body = (string) $request->getBody();

        // Discord deliberately sends bad signatures now and then, and removes the URL if one is accepted.
        if (! Signature::verify($key, $request->getHeaderLine('X-Signature-Ed25519'), $request->getHeaderLine('X-Signature-Timestamp'), $body)) {
            return new Response(Response::STATUS_UNAUTHORIZED);
        }

        $payload = json_decode($body);

        if (! is_object($payload) || ! isset($payload->type)) {
            return new Response(Response::STATUS_BAD_REQUEST);
        }

        if (self::TYPE_EVENT === $payload->type && isset($payload->event->type)) {
            $this->discord->getLoop()->futureTick(fn () => $this->deliver($payload->event, $body));
        }

        return new Response(Response::STATUS_NO_CONTENT, ['Content-Type' => 'application/json']);
    }

    /**
     * Emits an event, unless it is unknown or a copy of one already delivered.
     *
     * @param object $event The payload's `event` object.
     * @param string $body  The raw request body, which a retry repeats.
     */
    protected function deliver(object $event, string $body): void
    {
        if (! in_array($event->type, self::EVENTS, true)) {
            $this->discord->getLogger()->debug('ignoring unknown webhook event', ['type' => $event->type]);

            return;
        }

        $key = in_array($event->type, self::GATEWAY_EVENTS, true)
            ? DeliveredEvents::entitlementKey($event->type, $event->data ?? null)
            : sha1($body);

        if (! $this->delivered->remember($key)) {
            return;
        }

        ($this->dispatch)((object) [
            'op' => Op::OP_DISPATCH,
            's' => null,
            't' => $event->type,
            'd' => $event->data ?? (object) [],
        ]);
    }
}

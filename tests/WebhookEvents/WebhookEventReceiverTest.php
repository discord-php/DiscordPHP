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

use Discord\Discord;
use Discord\Parts\Lobby\Message;
use Discord\Parts\Monetization\Entitlement;
use Discord\Parts\OAuth\Application;
use Discord\WebhookEvents\Signature;
use Discord\WebhookEvents\WebhookEventReceiver;
use Discord\WebSockets\Event;
use React\Http\Browser;
use React\Http\Message\ServerRequest;

final class WebhookEventReceiverTest extends DiscordTestCase
{
    private static string $secret;

    private static string $public;

    public static function keys(): void
    {
        if (! isset(self::$secret)) {
            $pair = sodium_crypto_sign_keypair();
            self::$secret = sodium_crypto_sign_secretkey($pair);
            self::$public = bin2hex(sodium_crypto_sign_publickey($pair));
        }
    }

    public function testAPingIsAcknowledgedAndNotDispatched()
    {
        return wait(function (Discord $discord, $resolve) {
            [$receiver, $dispatched] = $this->receiver();

            $response = $receiver($this->signed(['version' => 1, 'application_id' => '7', 'type' => 0]));

            $this->assertSame(204, $response->getStatusCode());
            $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
            $this->assertSame('', (string) $response->getBody());

            $discord->getLoop()->futureTick(fn () => $resolve($this->assertSame([], $dispatched->packets)));
        });
    }

    public function testUnsignedOrBadlySignedRequestsAreRefused()
    {
        [$receiver] = $this->receiver();
        $body = json_encode(['version' => 1, 'application_id' => '7', 'type' => 0]);

        $unsigned = new ServerRequest('POST', 'http://localhost/', [], $body);
        $tampered = new ServerRequest('POST', 'http://localhost/', $this->signed(['version' => 1, 'application_id' => '7', 'type' => 0])->getHeaders(), $body.' ');
        $otherKey = $this->signed(['version' => 1, 'application_id' => '7', 'type' => 0], sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()));

        $this->assertSame(401, $receiver($unsigned)->getStatusCode());
        $this->assertSame(401, $receiver($tampered)->getStatusCode());
        $this->assertSame(401, $receiver($otherKey)->getStatusCode());
    }

    public function testOnlyPostIsAccepted()
    {
        [$receiver] = $this->receiver();

        $response = $receiver(new ServerRequest('GET', 'http://localhost/'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('POST', $response->getHeaderLine('Allow'));
    }

    public function testRequestsAreTurnedAwayUntilTheApplicationIsLoaded()
    {
        $mock = getMockDiscord();
        $receiver = new WebhookEventReceiver($mock, static fn () => null);

        $this->assertSame(503, $receiver($this->signed(['type' => 0]))->getStatusCode());
    }

    public function testAnEventIsAcknowledgedBeforeItIsDispatched()
    {
        return wait(function (Discord $discord, $resolve) {
            [$receiver, $dispatched] = $this->receiver();

            $response = $receiver($this->signed($this->event('LOBBY_MESSAGE_DELETE', ['id' => '9', 'lobby_id' => '1'])));

            $this->assertSame(204, $response->getStatusCode());
            $this->assertSame([], $dispatched->packets, 'nothing runs before the response is sent');

            $discord->getLoop()->futureTick(function () use ($dispatched, $resolve) {
                $this->assertCount(1, $dispatched->packets);
                $this->assertSame('LOBBY_MESSAGE_DELETE', $dispatched->packets[0]->t);
                $this->assertSame('9', $dispatched->packets[0]->d->id);
                $resolve();
            });
        });
    }

    public function testARetriedEventIsDispatchedOnce()
    {
        return wait(function (Discord $discord, $resolve) {
            [$receiver, $dispatched] = $this->receiver();
            $event = $this->event('LOBBY_MESSAGE_DELETE', ['id' => '9', 'lobby_id' => '1']);

            $receiver($this->signed($event));
            $receiver($this->signed($event));
            $receiver($this->signed($this->event('LOBBY_MESSAGE_DELETE', ['id' => '10', 'lobby_id' => '1'])));

            $discord->getLoop()->futureTick(fn () => $resolve($this->assertCount(2, $dispatched->packets)));
        });
    }

    public function testAnEntitlementTheGatewayDeliveredIsNotDeliveredAgain()
    {
        return wait(function (Discord $discord, $resolve) {
            [$receiver, $dispatched, $mock] = $this->receiver();
            $entitlement = ['id' => '3', 'sku_id' => '4', 'application_id' => '7', 'user_id' => '5', 'type' => 8, 'consumed' => false, 'deleted' => false];

            $mock->emit(Event::ENTITLEMENT_CREATE, [$mock->getFactory()->part(Entitlement::class, $entitlement, true), $mock]);

            $receiver($this->signed($this->event('ENTITLEMENT_CREATE', $entitlement + ['gift_code_flags' => 0, 'promotion_id' => null])));
            // The same entitlement, changed: a different event.
            $receiver($this->signed($this->event('ENTITLEMENT_UPDATE', ['consumed' => true] + $entitlement)));

            $discord->getLoop()->futureTick(function () use ($dispatched, $resolve) {
                $this->assertCount(1, $dispatched->packets);
                $this->assertSame('ENTITLEMENT_UPDATE', $dispatched->packets[0]->t);
                $resolve();
            });
        });
    }

    public function testUnknownEventsAreAcknowledgedAndDropped()
    {
        return wait(function (Discord $discord, $resolve) {
            [$receiver, $dispatched] = $this->receiver();

            // A signed READY must not reach the client's own gateway handling.
            $this->assertSame(204, $receiver($this->signed($this->event('READY', ['v' => 10])))->getStatusCode());
            $this->assertSame(204, $receiver($this->signed($this->event('QUEST_USER_ENROLLMENT', [])))->getStatusCode());

            $discord->getLoop()->futureTick(fn () => $resolve($this->assertSame([], $dispatched->packets)));
        });
    }

    public function testAVerifiedEventIsEmittedOnTheClient()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = $this->client();
            (fn () => $this->emittedInit = true)->call($mock);

            $mock->on(Event::LOBBY_MESSAGE_CREATE, function (Message $message, Discord $client) use ($mock, $resolve) {
                $this->assertSame($mock, $client);
                $this->assertSame('welcome to the party!', $message->content);
                $this->assertSame('party', $message->author->username);
                $resolve();
            });

            $mock->getWebhookEvents()($this->signed($this->event('LOBBY_MESSAGE_CREATE', [
                'id' => '9',
                'type' => 0,
                'content' => 'welcome to the party!',
                'lobby_id' => '1',
                'channel_id' => '1',
                'author' => ['id' => '5', 'username' => 'party', 'discriminator' => '0'],
                'flags' => 65536,
                'application_id' => '7',
            ])));
        });
    }

    public function testTheReceiverIsOnlyCreatedWhenAskedFor()
    {
        $mock = $this->client();

        $this->assertNull((fn () => $this->webhookEvents)->call($mock));
        $this->assertSame($mock->getWebhookEvents(), $mock->getWebhookEvents());
    }

    public function testItServesOverASocket()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = $this->client();
            $socket = $mock->getWebhookEvents()->listen('127.0.0.1:0');
            $url = str_replace('tcp://', 'http://', $socket->getAddress()).'/';
            $request = $this->signed(['version' => 1, 'application_id' => '7', 'type' => 0]);

            (new Browser($discord->getLoop()))
                ->post($url, $request->getHeaders(), (string) $request->getBody())
                ->then(fn ($response) => $this->assertSame(204, $response->getStatusCode()))
                ->finally(fn () => $mock->getWebhookEvents()->close())
                ->then($resolve, $resolve);
        });
    }

    public function testSignatureRejectsMalformedInputWithoutThrowing()
    {
        self::keys();

        $this->assertFalse(Signature::verify(self::$public, 'not-hex', '1', '{}'));
        $this->assertFalse(Signature::verify('short', str_repeat('a', 128), '1', '{}'));
        $this->assertFalse(Signature::verify(self::$public, str_repeat('a', 128), '', '{}'));
        $this->assertFalse(Signature::verify(self::$public, str_repeat('a', 128), '1', '{}'));
    }

    /**
     * A client for application 7, holding the test key pair's public key.
     */
    private function client(): Discord
    {
        self::keys();

        $mock = getMockDiscord();
        $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7', 'verify_key' => self::$public], true);

        return $mock;
    }

    /**
     * A receiver that records what it would dispatch instead of dispatching it.
     *
     * @return array{0: WebhookEventReceiver, 1: object{packets: object[]}, 2: Discord}
     */
    private function receiver(): array
    {
        $mock = $this->client();
        $dispatched = new class () {
            public array $packets = [];
        };

        return [new WebhookEventReceiver($mock, static function (object $packet) use ($dispatched) {
            $dispatched->packets[] = $packet;
        }), $dispatched, $mock];
    }

    /**
     * A webhook payload carrying one event.
     */
    private function event(string $type, array $data): array
    {
        return ['version' => 1, 'application_id' => '7', 'type' => 1, 'event' => ['type' => $type, 'timestamp' => '2025-08-05T21:44:09.412957', 'data' => $data]];
    }

    /**
     * A request signed the way Discord signs them.
     */
    private function signed(array $payload, ?string $secret = null): ServerRequest
    {
        self::keys();

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = bin2hex(sodium_crypto_sign_detached($timestamp.$body, $secret ?? self::$secret));

        return new ServerRequest('POST', 'http://localhost/', ['X-Signature-Ed25519' => $signature, 'X-Signature-Timestamp' => $timestamp, 'Content-Type' => 'application/json'], $body);
    }
}

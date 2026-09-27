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
use Discord\MessageCommandClient;
use Discord\Parts\Guild\Sound;
use Discord\Parts\Monetization\Subscription;
use Discord\Parts\OAuth\Application;
use Discord\Parts\WebSockets\RateLimited as RateLimitedPart;
use Discord\Parts\WebSockets\VoiceChannelEffect;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\GuildSoundboardSoundsUpdate;
use Discord\WebSockets\Events\RateLimited;
use Discord\WebSockets\Events\SubscriptionCreate;
use Discord\WebSockets\Events\SubscriptionDelete;
use Discord\WebSockets\Events\SubscriptionUpdate;
use Discord\WebSockets\Events\VoiceChannelEffectSend;
use Discord\WebSockets\Handlers;
use Discord\WebSockets\Op;
use Psr\Log\NullLogger;

use function Discord\promiseFromGenerator;

/**
 * Gateway events Discord documents that DiscordPHP did not handle before.
 */
final class GatewayEventsTest extends DiscordTestCase
{
    public function testTheEventsHaveHandlers()
    {
        $handlers = new Handlers();

        foreach ([
            Event::RATE_LIMITED => RateLimited::class,
            Event::VOICE_CHANNEL_EFFECT_SEND => VoiceChannelEffectSend::class,
            Event::GUILD_SOUNDBOARD_SOUNDS_UPDATE => GuildSoundboardSoundsUpdate::class,
            Event::SUBSCRIPTION_CREATE => SubscriptionCreate::class,
            Event::SUBSCRIPTION_UPDATE => SubscriptionUpdate::class,
            Event::SUBSCRIPTION_DELETE => SubscriptionDelete::class,
        ] as $event => $class) {
            $this->assertSame($class, $handlers->getHandler($event)['class'] ?? null, $event);
        }
    }

    public function testAVoiceChannelEffectSaysWhoSentWhat()
    {
        $mock = getMockDiscord();
        $mock->sounds->pushItem($mock->getFactory()->part(Sound::class, ['sound_id' => '1', 'name' => 'quack', 'volume' => 1.0], true));

        /** @var VoiceChannelEffect */
        $effect = (new VoiceChannelEffectSend($mock))->handle((object) [
            'channel_id' => '20',
            'guild_id' => '10',
            'user_id' => '5',
            'emoji' => (object) ['id' => null, 'name' => '🦆'],
            'animation_type' => VoiceChannelEffect::ANIMATION_TYPE_BASIC,
            'animation_id' => 4,
            'sound_id' => 1,
            'sound_volume' => 0.5,
        ]);

        $this->assertInstanceOf(VoiceChannelEffect::class, $effect);
        $this->assertSame('5', $effect->user_id);
        $this->assertSame('🦆', $effect->emoji->name);
        $this->assertSame(VoiceChannelEffect::ANIMATION_TYPE_BASIC, $effect->animation_type);
        $this->assertSame('quack', $effect->sound?->name, 'a default sound is found by its numeric ID');
        $this->assertSame(0.5, $effect->sound_volume);
    }

    public function testARateLimitSaysWhatWasRefused()
    {
        /** @var RateLimitedPart */
        $rateLimit = (new RateLimited(getMockDiscord()))->handle((object) [
            'opcode' => Op::OP_REQUEST_GUILD_MEMBERS,
            'retry_after' => 1.5,
            'meta' => (object) ['guild_id' => '1001', 'nonce' => 'n1'],
        ]);

        $this->assertSame(Op::OP_REQUEST_GUILD_MEMBERS, $rateLimit->opcode);
        $this->assertSame(1.5, $rateLimit->retry_after);
        $this->assertSame('1001', $rateLimit->guild_id);
        $this->assertSame('n1', $rateLimit->nonce);
    }

    public function testTheClientsOwnMemberRequestIsSentAgainAfterTheWait()
    {
        return wait(function (Discord $discord, $resolve) {
            $client = $this->recordingClient();
            (fn () => $this->largeSent = ['1001'])->call($client);

            $this->dispatchRateLimit($client, '1001', 0.05);

            // Until the wait is over the guild still counts as sent, so the client cannot become ready without it.
            $this->assertSame(['1001'], (fn () => $this->largeSent)->call($client));
            $this->assertSame([], $client->sent);

            $discord->getLoop()->addTimer(0.2, function () use ($client, $resolve) {
                $this->assertCount(1, $client->sent);
                $this->assertSame(Op::OP_REQUEST_GUILD_MEMBERS, $client->sent[0]->op);
                $this->assertSame('1001', $client->sent[0]->d['guild_id']);
                $this->assertSame(['1001'], (fn () => $this->largeSent)->call($client), 'it is waited for again');
                $resolve();
            });
        });
    }

    public function testSomeoneElsesRateLimitedRequestIsLeftAlone()
    {
        return wait(function (Discord $discord, $resolve) {
            $client = $this->recordingClient();

            $this->dispatchRateLimit($client, '2002', 0.0);

            $discord->getLoop()->addTimer(0.1, function () use ($client, $resolve) {
                $this->assertSame([], $client->sent, 'only the client\'s own member requests are sent again');
                $resolve();
            });
        });
    }

    public function testADeletedSubscriptionIsNoLongerCreated()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $mock->application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

            promiseFromGenerator((new SubscriptionDelete($mock))->handle((object) ['id' => '90', 'user_id' => '5', 'sku_ids' => ['91'], 'status' => 2]))
                ->then(function (Subscription $subscription) {
                    $this->assertSame('90', $subscription->id);
                    $this->assertFalse($subscription->created);
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * A client that records what it would send over the gateway, which a test client has no connection for.
     */
    private function recordingClient(): Discord
    {
        $client = new class(['token' => '', 'logger' => new NullLogger()]) extends MessageCommandClient {
            public array $sent = [];

            public function send(object|array $data, bool $force = false): void
            {
                $this->sent[] = $data;
            }
        };
        $client->getHttpClient()->setDriver(getMockHttpDriver(fn () => null));

        return $client;
    }

    private function dispatchRateLimit(Discord $client, string $guild_id, float $retry_after): void
    {
        $packet = (object) [
            'op' => Op::OP_DISPATCH,
            't' => Event::RATE_LIMITED,
            's' => 1,
            'd' => (object) ['opcode' => Op::OP_REQUEST_GUILD_MEMBERS, 'retry_after' => $retry_after, 'meta' => (object) ['guild_id' => $guild_id]],
        ];

        (fn () => $this->handleDispatch($packet))->call($client);
    }
}

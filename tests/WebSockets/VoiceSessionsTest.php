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
use Discord\Helpers\CacheConfig;
use Discord\Helpers\CacheWrapper;
use Discord\Parts\WebSockets\VoiceServerUpdate as VoiceServerUpdatePart;
use Discord\Parts\WebSockets\VoiceSession;
use Discord\Repository\VoiceSessionRepository;
use Discord\WebSockets\Events\VoiceServerUpdate;
use Discord\WebSockets\Events\VoiceStateUpdate;
use React\Cache\CacheInterface;
use React\Promise\PromiseInterface;

use function Discord\promiseFromGenerator;
use function React\Promise\resolve;

/**
 * The bot's voice session in each guild: kept in a repository from its own voice state and the voice
 * server, so it is stored through the cache like everything else (#1441).
 */
final class VoiceSessionsTest extends DiscordTestCase
{
    private const BOT = '999';

    private const GUILD = '10';

    public function testTheClientKeepsVoiceSessionsInARepositoryByGuild()
    {
        $discord = $this->client();

        $this->assertInstanceOf(VoiceSessionRepository::class, $discord->voice_sessions);
        $this->assertSame('guild_id', $discord->voice_sessions->discrim);
        $this->assertSame($discord->voice_sessions, $discord->voice_sessions, 'the same repository every time');
    }

    public function testTheBotsOwnVoiceStateRecordsItsSession()
    {
        $discord = $this->client();

        $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => '20', 'session_id' => 'session-1']);

        $session = $discord->voice_sessions->get('guild_id', self::GUILD);
        $this->assertInstanceOf(VoiceSession::class, $session);
        $this->assertSame(self::GUILD, $session->guild_id);
        $this->assertSame('20', $session->channel_id);
        $this->assertSame(self::BOT, $session->user_id);
        $this->assertSame('session-1', $session->session_id);
        $this->assertFalse($session->isResumable(), 'no voice server yet');
    }

    public function testSomeoneElsesVoiceStateIsNotASession()
    {
        $discord = $this->client();

        $this->voiceState($discord, ['user_id' => '5', 'channel_id' => '20', 'session_id' => 'theirs']);

        $this->assertNull($discord->voice_sessions->get('guild_id', self::GUILD));
    }

    public function testTheVoiceServerJoinsTheSameSessionWhicheverArrivesFirst()
    {
        foreach (['state first' => true, 'server first' => false] as $order => $stateFirst) {
            $discord = $this->client();

            if ($stateFirst) {
                $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => '20', 'session_id' => 'session-1']);
            }

            /** @var VoiceServerUpdatePart */
            $server = $this->dispatch(new VoiceServerUpdate($discord), (object) ['guild_id' => self::GUILD, 'token' => 'voice-token', 'endpoint' => 'c-dfw.discord.media:443']);
            $this->assertInstanceOf(VoiceServerUpdatePart::class, $server, 'listeners still get the server update');

            if (! $stateFirst) {
                $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => '20', 'session_id' => 'session-1']);
            }

            $session = $discord->voice_sessions->get('guild_id', self::GUILD);
            $this->assertSame('session-1', $session?->session_id, $order);
            $this->assertSame('voice-token', $session?->token, $order);
            $this->assertSame('c-dfw.discord.media:443', $session?->endpoint, $order);
            $this->assertTrue($session?->isResumable(), $order);
        }
    }

    public function testMovingChannelKeepsTheServer()
    {
        $discord = $this->client();
        $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => '20', 'session_id' => 'session-1']);
        $this->dispatch(new VoiceServerUpdate($discord), (object) ['guild_id' => self::GUILD, 'token' => 'voice-token', 'endpoint' => 'c-dfw.discord.media:443']);

        $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => '21', 'session_id' => 'session-1']);

        $session = $discord->voice_sessions->get('guild_id', self::GUILD);
        $this->assertSame('21', $session->channel_id);
        $this->assertSame('voice-token', $session->token);
    }

    public function testLeavingVoiceRemovesTheSession()
    {
        $discord = $this->client();
        $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => '20', 'session_id' => 'session-1']);

        $this->voiceState($discord, ['user_id' => self::BOT, 'channel_id' => null, 'session_id' => 'session-1']);

        $this->assertNull($discord->voice_sessions->get('guild_id', self::GUILD));
    }

    public function testTheTokenIsNotShownButIsKept()
    {
        $discord = $this->client();
        $session = $discord->getFactory()->part(VoiceSession::class, ['guild_id' => self::GUILD, 'session_id' => 'session-1', 'token' => 'voice-token'], true);

        $this->assertArrayNotHasKey('token', $session->jsonSerialize());
        $this->assertArrayNotHasKey('token', $session->__debugInfo());
        $this->assertSame('voice-token', $session->getRawAttributes()['token'], 'resuming needs it');
    }

    public function testASessionSurvivesACacheThatSerialises()
    {
        $discord = $this->client();
        $items = [];
        $class = VoiceSession::class;
        $cache = new CacheWrapper($discord, new CacheConfig($this->serialisingCache()), $items, $class, []);

        $cache->set(self::GUILD, $discord->getFactory()->part(VoiceSession::class, [
            'guild_id' => self::GUILD,
            'channel_id' => '20',
            'user_id' => self::BOT,
            'session_id' => 'session-1',
            'token' => 'voice-token',
            'endpoint' => 'c-dfw.discord.media:443',
        ], true));
        $items = [];

        $restored = null;
        $cache->get(self::GUILD)->then(function ($part) use (&$restored) {
            $restored = $part;
        });

        $this->assertInstanceOf(VoiceSession::class, $restored);
        $this->assertTrue($restored->created);
        $this->assertSame('session-1', $restored->session_id);
        $this->assertSame('voice-token', $restored->token);
        $this->assertTrue($restored->isResumable());
        $this->assertSame('VoiceSession.', $cache->getPrefix(), 'one key per guild, shared by every client using the cache');
    }

    /** A client that is not connected, whose bot user is BOT. */
    private function client(): Discord
    {
        $discord = getMockDiscord();
        $discord->id = self::BOT;

        return $discord;
    }

    /** @param array<string, mixed> $state */
    private function voiceState(Discord $discord, array $state): mixed
    {
        return $this->dispatch(new VoiceStateUpdate($discord), (object) ($state + [
            'guild_id' => self::GUILD,
            'deaf' => false,
            'mute' => false,
            'self_deaf' => true,
            'self_mute' => false,
            'self_video' => false,
            'suppress' => false,
        ]));
    }

    /** Runs a handler to the end: every cache here answers at once, so its promise settles at once. */
    private function dispatch(object $handler, object $data): mixed
    {
        $result = $handler->handle($data);

        if (! $result instanceof \Generator) {
            return $result;
        }

        $value = null;
        $failure = null;
        promiseFromGenerator($result)->then(
            function ($resolved) use (&$value) {
                $value = $resolved;
            },
            function (\Throwable $e) use (&$failure) {
                $failure = $e;
            },
        );

        if ($failure !== null) {
            throw $failure;
        }

        return $value;
    }

    /** A cache that stores strings, as Redis would, so parts go through serialize() and back. */
    private function serialisingCache(): CacheInterface
    {
        return new class() implements CacheInterface {
            /** @var array<string, string> */
            private array $data = [];

            public function get($key, $default = null): PromiseInterface
            {
                return resolve($this->data[$key] ?? $default);
            }

            public function set($key, $value, $ttl = null): PromiseInterface
            {
                if (! is_string($value)) {
                    throw new \LogicException('only strings reach a serialising cache');
                }

                $this->data[$key] = $value;

                return resolve(true);
            }

            public function delete($key): PromiseInterface
            {
                unset($this->data[$key]);

                return resolve(true);
            }

            public function getMultiple(array $keys, $default = null): PromiseInterface
            {
                return resolve(array_map(fn ($key) => $this->data[$key] ?? $default, array_combine($keys, $keys)));
            }

            public function setMultiple(array $values, $ttl = null): PromiseInterface
            {
                $this->data = $values + $this->data;

                return resolve(true);
            }

            public function deleteMultiple(array $keys): PromiseInterface
            {
                foreach ($keys as $key) {
                    unset($this->data[$key]);
                }

                return resolve(true);
            }

            public function clear(): PromiseInterface
            {
                $this->data = [];

                return resolve(true);
            }

            public function has($key): PromiseInterface
            {
                return resolve(isset($this->data[$key]));
            }
        };
    }
}

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

use Discord\Helpers\CacheConfig;
use Discord\Helpers\CacheWrapper;
use Discord\Parts\WebSockets\VoiceSession;
use React\Cache\CacheInterface;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * The bot's voice session in one guild: its token stays out of output, and it survives being stored
 * through a cache (#1441).
 */
final class VoiceSessionTest extends DiscordTestCase
{
    private const GUILD = '10';

    public function testTheTokenIsNotShownButIsKept()
    {
        $session = getMockDiscord()->getFactory()->part(VoiceSession::class, ['guild_id' => self::GUILD, 'session_id' => 'session-1', 'token' => 'voice-token'], true);

        $this->assertArrayNotHasKey('token', $session->jsonSerialize());
        $this->assertArrayNotHasKey('token', $session->__debugInfo());
        $this->assertSame('voice-token', $session->getRawAttributes()['token'], 'resuming needs it');
    }

    public function testASessionSurvivesACacheThatSerialises()
    {
        $discord = getMockDiscord();
        $items = [];
        $class = VoiceSession::class;
        $cache = new CacheWrapper($discord, new CacheConfig($this->serialisingCache()), $items, $class, []);

        $cache->set(self::GUILD, $discord->getFactory()->part(VoiceSession::class, [
            'guild_id' => self::GUILD,
            'channel_id' => '20',
            'user_id' => '999',
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

    /** A cache that stores strings, as Redis would, so parts go through serialize() and back. */
    private function serialisingCache(): CacheInterface
    {
        return new class() implements CacheInterface {
            /** @var array<string, string> */
            private array $data = [];

            /**
             * @inheritDoc
             */
            public function get($key, $default = null): PromiseInterface
            {
                return resolve($this->data[$key] ?? $default);
            }

            /**
             * @inheritDoc
             */
            public function set($key, $value, $ttl = null): PromiseInterface
            {
                if (! is_string($value)) {
                    throw new \LogicException('only strings reach a serialising cache');
                }

                $this->data[$key] = $value;

                return resolve(true);
            }

            /**
             * @inheritDoc
             */
            public function delete($key): PromiseInterface
            {
                unset($this->data[$key]);

                return resolve(true);
            }

            /**
             * @inheritDoc
             */
            public function getMultiple(array $keys, $default = null): PromiseInterface
            {
                return resolve(array_map(fn ($key) => $this->data[$key] ?? $default, array_combine($keys, $keys)));
            }

            /**
             * @inheritDoc
             */
            public function setMultiple(array $values, $ttl = null): PromiseInterface
            {
                foreach ($values as $key => $value) {
                    $this->set($key, $value, $ttl);
                }

                return resolve(true);
            }

            /**
             * @inheritDoc
             */
            public function deleteMultiple(array $keys): PromiseInterface
            {
                foreach ($keys as $key) {
                    unset($this->data[$key]);
                }

                return resolve(true);
            }

            /**
             * @inheritDoc
             */
            public function clear(): PromiseInterface
            {
                $this->data = [];

                return resolve(true);
            }

            /**
             * @inheritDoc
             */
            public function has($key): PromiseInterface
            {
                return resolve(isset($this->data[$key]));
            }
        };
    }
}

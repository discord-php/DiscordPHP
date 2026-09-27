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
use Psr\Log\NullLogger;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;
use React\Promise\Deferred;

/**
 * Gateway transport compression: a fresh decompression context for every connection, and
 * zstd-stream, where each message is one gateway payload that does not end the zstd frame.
 */
final class TransportCompressionTest extends DiscordTestCase
{
    public function testEveryConnectionStartsWithFreshDecompressors(): void
    {
        $client = getMockDiscord();
        $set = static fn (string $name, $value) => (new ReflectionProperty(Discord::class, $name))->setValue($client, $value);
        $get = static fn (string $name) => (new ReflectionProperty(Discord::class, $name))->getValue($client);
        $connect = static fn () => (new ReflectionMethod(Discord::class, 'buildParams'))->invoke($client, new Deferred(), 'wss://gateway.discord.gg');

        // Left over from the previous connection.
        $set('zstdDecompressor', new stdClass());
        $set('payloadBuffer', "\x78\x9c");

        $set('useTransportCompression', false);
        $connect();
        $this->assertFalse($get('zstdDecompressor'));
        $this->assertFalse($get('zlibDecompressor'));
        $this->assertSame('', $get('payloadBuffer'));

        $set('useTransportCompression', true);
        $connect();
        if (function_exists('zstd_uncompress_init')) {
            $this->assertNotFalse($get('zstdDecompressor'));
            $this->assertFalse($get('zlibDecompressor'));
        } else {
            $this->assertFalse($get('zstdDecompressor'));
            $this->assertInstanceOf(InflateContext::class, $get('zlibDecompressor'));
        }
    }

    public function testEachZstdStreamMessageDecodesToItsOwnPayload(): void
    {
        if (! function_exists('zstd_compress_init') || ! function_exists('zstd_uncompress_init')) {
            $this->markTestSkipped('ext-zstd with the incremental API is not loaded.');
        }

        $client = new class(['token' => '', 'logger' => new NullLogger()]) extends Discord {
            /** @var list<string> */
            public array $seen = [];

            public function connectWs(): void
            {
                // Only the decoding is under test. A real connection would never identify, since
                // processWsMessage() below only records, so the gateway would drop it and the
                // client reconnect for good, keeping the event loop and the test run alive.
            }

            protected function processWsMessage(string $data): void
            {
                $this->seen[] = $data;
            }
        };
        (new ReflectionProperty(Discord::class, 'zstdDecompressor'))->setValue($client, zstd_uncompress_init());

        $payloads = [
            json_encode(['op' => 10, 'd' => ['heartbeat_interval' => 41250]]),
            json_encode(['op' => 0, 't' => 'READY', 's' => 1, 'd' => ['v' => 10]]),
            // Bigger than the 128 KiB a single zstd block holds.
            json_encode(['op' => 0, 't' => 'GUILD_CREATE', 's' => 2, 'd' => ['members' => array_fill(0, 4000, ['user' => ['id' => '80351110224678912', 'username' => str_repeat('x', 32)]])]]),
        ];

        // What the gateway sends: one shared context, each message flushed but the frame left open.
        $compressor = zstd_compress_init();
        foreach ($payloads as $payload) {
            $message = new Message();
            $message->addFrame(new Frame(zstd_compress_add($compressor, $payload, false), true, Frame::OP_BINARY));
            $client->handleWsMessage($message);
        }

        $this->assertSame($payloads, $client->seen);
    }
}

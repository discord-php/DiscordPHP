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

use Discord\RPC\FrameDecoder;
use Discord\RPC\ProtocolException;
use PHPUnit\Framework\Attributes\DataProvider;

/** @SuppressWarnings(PHPMD.StaticAccess) Tests the stateless wire encoder. */
final class FrameDecoderTest extends DiscordTestCase
{
    protected static function isLive(): bool
    {
        return false;
    }

    public function testFragmentedAndCoalescedFrames(): void
    {
        $frame = FrameDecoder::encode(1, '{"cmd":"DISPATCH","evt":"READY"}');
        for ($split = 1; $split < strlen($frame); ++$split) {
            $decoder = new FrameDecoder();
            $decoder->append(substr($frame, 0, $split));
            $this->assertNull($decoder->next());
            $decoder->append(substr($frame, $split).FrameDecoder::encode(3, "\x00ping\xff"));
            $this->assertSame([1, substr($frame, 8)], $decoder->next());
            $this->assertSame([3, "\x00ping\xff"], $decoder->next());
            $this->assertNull($decoder->next());
        }
    }

    #[DataProvider('invalidHeaders')]
    public function testRejectsInvalidHeadersBeforePayloadArrives(string $header): void
    {
        $decoder = new FrameDecoder(16, 24);
        $decoder->append($header);
        $this->expectException(ProtocolException::class);
        $decoder->next();
    }

    public static function invalidHeaders(): array
    {
        return [[pack('VV', 1, 17)], [pack('VV', 1, 0xffffffff)], [pack('VV', 0, 0)], [pack('VV', 99, 0)]];
    }

    public function testAggregateBufferLimitAndClear(): void
    {
        $decoder = new FrameDecoder(8, 16);
        $decoder->append('partial');
        $decoder->clear();
        $decoder->append(FrameDecoder::encode(4, ''));
        $this->assertSame([4, ''], $decoder->next());
        $this->expectException(ProtocolException::class);
        $decoder->append(str_repeat('x', 17));
    }
}

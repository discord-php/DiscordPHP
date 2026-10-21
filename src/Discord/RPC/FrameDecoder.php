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

/** Incremental, bounded little-endian IPC framing; JSON interpretation belongs to the client. */
final class FrameDecoder
{
    private string $buffer = '';

    public function __construct(private int $maxFrameBytes = 1048576, private int $maxBufferBytes = 2097168)
    {
        if ($maxFrameBytes < 1 || $maxBufferBytes < $maxFrameBytes + 8) {
            throw new \InvalidArgumentException('Invalid IPC decoder limits.');
        }
    }

    public function append(string $bytes): void
    {
        if (strlen($bytes) > $this->maxBufferBytes - strlen($this->buffer)) {
            throw new ProtocolException('IPC receive buffer limit exceeded.');
        }
        $this->buffer .= $bytes;
    }

    /** @return array{int, string}|null A complete opcode/payload pair, or null until more bytes arrive. */
    public function next(): ?array
    {
        if (strlen($this->buffer) < 8) {
            return null;
        }
        $header = unpack('Vopcode/Vlength', substr($this->buffer, 0, 8));
        if ($header['length'] > $this->maxFrameBytes) {
            throw new ProtocolException('IPC frame limit exceeded.');
        }
        if (! in_array($header['opcode'], [1, 2, 3, 4], true)) {
            throw new ProtocolException('Unexpected IPC opcode.');
        }
        $length = $header['length'];
        if (strlen($this->buffer) < 8 + $length) {
            return null;
        }
        $payload = substr($this->buffer, 8, $length);
        $this->buffer = substr($this->buffer, 8 + $length);

        return [$header['opcode'], $payload];
    }

    public function clear(): void
    {
        $this->buffer = '';
    }

    public static function encode(int $opcode, string $payload): string
    {
        return pack('VV', $opcode, strlen($payload)).$payload;
    }
}

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

/**
 * @internal Decodes envelope structure without interpreting command-specific data.
 *
 * @since 10.69.0
 */
final class RpcEnvelope
{
    public static function decode(int $opcode, string $payload): array
    {
        try {
            $object = json_decode($payload, false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new ProtocolException('Invalid IPC JSON.');
        }
        if (! $object instanceof \stdClass) {
            throw new ProtocolException('IPC payload must be an object.');
        }
        $raw = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
        if ($opcode === 2) {
            self::validateClose($raw);

            return $raw;
        }
        self::validateFrame($raw);
        if (! isset($raw['nonce'])) {
            self::validateDispatch($raw);
        }

        return $raw;
    }

    private static function validateClose(array $raw): void
    {
        if (! isset($raw['code'], $raw['message']) || ! is_int($raw['code']) || ! is_string($raw['message'])) {
            throw new ProtocolException('Invalid IPC CLOSE payload.');
        }
    }

    private static function validateFrame(array $raw): void
    {
        if (! isset($raw['cmd']) || ! is_string($raw['cmd']) || ! preg_match('/^[A-Z_]+$/D', $raw['cmd'])) {
            throw new ProtocolException('Invalid RPC command envelope.');
        }
        foreach (['evt', 'nonce'] as $key) {
            if (isset($raw[$key]) && ! is_string($raw[$key])) {
                throw new ProtocolException('Invalid RPC event or nonce.');
            }
        }
    }

    private static function validateDispatch(array $raw): void
    {
        if ($raw['cmd'] !== 'DISPATCH' || ! isset($raw['evt'])) {
            throw new ProtocolException('Uncorrelated RPC response.');
        }
        if ($raw['evt'] === 'ERROR') {
            throw new ProtocolException('Uncorrelated RPC server error.');
        }
    }
}

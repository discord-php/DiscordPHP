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

use Discord\Interaction;
use Elliptic\EdDSA;

/**
 * Checks the Ed25519 signature Discord puts on every request it sends to an application.
 *
 * Uses PHP's bundled sodium extension, falling back to `simplito/elliptic-php`, which is pure PHP but much slower.
 *
 * @link https://docs.discord.com/developers/interactions/overview#setting-up-an-endpoint-validating-security-request-headers
 *
 * @since 10.59.0
 */
final class Signature
{
    /**
     * Whether a request was signed by Discord for this application.
     *
     * @param string $publicKey The application's hex-encoded `verify_key`.
     * @param string $signature The `X-Signature-Ed25519` header.
     * @param string $timestamp The `X-Signature-Timestamp` header.
     * @param string $body      The raw request body.
     *
     * @throws \RuntimeException Neither sodium nor `simplito/elliptic-php` is available.
     */
    public static function verify(string $publicKey, string $signature, string $timestamp, string $body): bool
    {
        if ('' === $timestamp || ! preg_match('/^[0-9a-f]{64}$/i', $publicKey) || ! preg_match('/^[0-9a-f]{128}$/i', $signature)) {
            return false;
        }

        if (function_exists('sodium_crypto_sign_verify_detached')) {
            return sodium_crypto_sign_verify_detached(hex2bin($signature), $timestamp.$body, hex2bin($publicKey));
        }

        self::assertAvailable();

        return Interaction::verifyKey($body, $signature, $timestamp, $publicKey);
    }

    /**
     * @throws \RuntimeException Neither sodium nor `simplito/elliptic-php` is available.
     */
    public static function assertAvailable(): void
    {
        if (! function_exists('sodium_crypto_sign_verify_detached') && ! class_exists(EdDSA::class)) {
            throw new \RuntimeException('Checking Discord\'s request signatures needs the sodium extension, which PHP bundles (enable `extension=sodium` in php.ini), or the simplito/elliptic-php package.');
        }
    }
}

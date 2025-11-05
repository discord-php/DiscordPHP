<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

/**
 * Provides group-based AEAD encryption and decryption for Discord voice RTP packets.
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 *
 * @since 10.41.0
 *
 * @property string $secret_key  The group secret used for key derivation.
 * @property int    $nonceLength Nonce length based on encryption mode.
 * @property string $mode        The encryption mode.
 *
 * @method string       encrypt(string $plaintext, string $header = '', int $seq = 0)          Encrypt a message for Discord's MLS Group.
 * @method string       decrypt(string $ciphertext, string $header = '', int $seq = 0)         Decrypt a message from Discord's MLS Group.
 * @method string       encryptRTPPacket(string $rtpHeader, string $opusPayload, int $seq = 0) Encrypt an RTP packet (header + Opus payload).
 * @method string|false decryptRTPPacket(string $packet, int $seq = 0)                         Decrypt an RTP packet (header + encrypted payload).
 */
class VoiceGroupCrypto implements VoiceGroupCryptoInterface
{
    use VoiceGroupCryptoTrait;

    /**
     * Supported encryption modes for voice connections.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes
     *
     * @var string[] The supported transport encryption modes.
     */
    public const SUPPORTED_MODES = [
        'aead_aes256_gcm_rtpsize',
        'aead_xchacha20_poly1305_rtpsize',
    ];

    /**
     * @param string $secret_key Optional group secret.
     * @param string $mode       The supported transport encryption mode.
     */
    public function __construct(public string $secret_key, string $mode = 'aead_xchacha20_poly1305_rtpsize')
    {
        if (! in_array($mode, self::SUPPORTED_MODES)) {
            throw new \InvalidArgumentException("Invalid transport encryption mode: {$mode}");
        }

        $this->mode = strtolower($mode);

        switch ($this->mode) {
            case 'aead_aes256_gcm_rtpsize':
                if (! defined('SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES')) {
                    throw new \RuntimeException('AES256-GCM not supported');
                }
                $this->nonceLength = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES; // 12
                //$this->keyLength = SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES;
                break;

            case 'aead_xchacha20_poly1305_rtpsize':
                $this->nonceLength = 24; // RTP header + 12 zero bytes
                //$this->keyLength = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES;
                break;
        }
    }
}

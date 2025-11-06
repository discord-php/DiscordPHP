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
 * @property string $secret_key The group secret used for key derivation.
 * @property string $mode       The encryption mode.
 *
 * @method string       encryptRTPPacket(VoicePacket $packet, int $seq = 0) Encrypt an RTP packet (header + Opus payload).
 * @method string|false decryptRTPPacket(VoicePacket $packet, int $seq = 0) Decrypt an RTP packet (header + encrypted payload).
 */
trait VoiceGroupCryptoTrait
{
    //protected int $nonceLength;
    //protected int $keyLength;
    protected string $mode;

    /**
     * Encrypt a message for Discord's MLS Group.
     *
     * @param string $plaintext
     * @param string $header    Optional 12-byte RTP header (for RTP-style nonce)
     * @param int    $seq       Optional sequence number (for AES-GCM)
     */
    protected function encrypt(string $plaintext, string $header = '', int $seq = 0): string
    {
        $nonce = $this->buildNonce($header, $seq);

        return match ($this->mode) {
            'aead_aes256_gcm_rtpsize' => sodium_crypto_aead_aes256gcm_encrypt($plaintext, '', $nonce, $this->secret_key),
            'aead_xchacha20_poly1305_rtpsize' => sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $this->secret_key),
        };
    }

    /**
     * Decrypt a message from Discord's MLS Group.
     *
     * @param string $ciphertext
     * @param string $header     Optional RTP header
     * @param int    $seq        Optional sequence number
     */
    protected function decrypt(string $ciphertext, string $header = '', int $seq = 0): string|false
    {
        $nonce = $this->buildNonce($header, $seq);

        $plaintext = match ($this->mode) {
            'aead_aes256_gcm_rtpsize' => sodium_crypto_aead_aes256gcm_decrypt($ciphertext, '', $nonce, $this->secret_key),
            'aead_xchacha20_poly1305_rtpsize' => sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertext, '', $nonce, $this->secret_key),
        };

        return $plaintext;
    }

    /**
     * Build a nonce for RTP-style AEAD.
     */
    protected function buildNonce(string $header = '', int $seq = 0): string
    {
        // Ensure header is exactly 12 bytes (truncate or pad)
        $header12 = str_pad(substr($header, 0, 12), 12, "\x00", STR_PAD_RIGHT);

        return match ($this->mode) {
            // Protocol uses a 4-byte truncated nonce; expand to 12 bytes by
            // setting the 8 most-significant bytes to zero and placing the
            // 4-byte nonce in the least-significant bytes.
            'aead_aes256_gcm_rtpsize' => str_repeat("\x00", 8).pack('N', $seq),
            // XChaCha20-Poly1305 uses a 24-byte nonce formed from the RTP header (12)
            // followed by 12 zero bytes.
            'aead_xchacha20_poly1305_rtpsize' => $header12.str_repeat("\x00", 12),
        };
    }

    /**
     * Encrypt an RTP packet (header + Opus payload).
     *
     * @param string $rtpHeader   12-byte RTP header
     * @param string $opusPayload Opus-encoded audio
     * @param int    $seq         Sequence number for AES-GCM mode
     */
    public function encryptRTPPacket(VoicePacket $packet, int $seq = 0): string
    {
        $cipher = $this->encrypt($packet->getData(), $packet->getHeader(), $seq);

        return $packet->getHeader().$cipher;
    }

    /**
     * Decrypt an RTP packet (header + encrypted payload).
     */
    public function decryptRTPPacket(VoicePacket $packet, int $seq = 0): string|false
    {
        $ciphertext = $packet->getData();
        
        return $this->decrypt($ciphertext, $packet->getHeader(), $seq);
    }
}

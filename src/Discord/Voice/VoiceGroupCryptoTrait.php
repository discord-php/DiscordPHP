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
trait VoiceGroupCryptoTrait
{
    protected int $nonceLength;
    //protected int $keyLength;
    protected string $mode;

    /**
     * Encrypt a message for Discord's MLS Group.
     *
     * @param string $plaintext
     * @param string $header    Optional 12-byte RTP header (for RTP-style nonce)
     * @param int    $seq       Optional sequence number (for AES-GCM)
     */
    public function encrypt(string $plaintext, string $header = '', int $seq = 0): string
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
    public function decrypt(string $ciphertext, string $header = '', int $seq = 0): string
    {
        $nonce = $this->buildNonce($header, $seq);

        $plaintext = match ($this->mode) {
            'aead_aes256_gcm_rtpsize' => sodium_crypto_aead_aes256gcm_decrypt($ciphertext, '', $nonce, $this->secret_key),
            'aead_xchacha20_poly1305_rtpsize' => sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertext, '', $nonce, $this->secret_key),
        };

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }

    /**
     * Build a nonce for RTP-style AEAD.
     */
    protected function buildNonce(string $header = '', int $seq = 0): string
    {
        return match ($this->mode) {
            'aead_aes256_gcm_rtpsize' => pack('N', $seq).str_repeat("\x00", 8),
            'aead_xchacha20_poly1305_rtpsize' => str_pad($header, 12, "\x00", STR_PAD_RIGHT).str_repeat("\x00", 12),
        };
    }

    /**
     * Encrypt an RTP packet (header + Opus payload).
     *
     * @param string $rtpHeader   12-byte RTP header
     * @param string $opusPayload Opus-encoded audio
     * @param int    $seq         Sequence number for AES-GCM mode
     */
    public function encryptRTPPacket(string $rtpHeader, string $opusPayload, int $seq = 0): string
    {
        $cipher = $this->encrypt($opusPayload, $rtpHeader, $seq);

        return $rtpHeader.$cipher;
    }

    /**
     * Decrypt an RTP packet (header + encrypted payload).
     */
    public function decryptRTPPacket(string $packet, int $seq = 0): string|false
    {
        if (strlen($packet) < 12) {
            return false;
        }
        $header = substr($packet, 0, 12);
        $payloadEnc = substr($packet, 12);

        return $this->decrypt($payloadEnc, $header, $seq);
    }
}

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
 * @property string $secret_key The encryption secret from session description.
 * @property string $mode       The encryption mode.
 *
 * @method string       encryptRTPPacket(VoicePacket $packet, int $seq = 0) Encrypt an RTP packet (header + Opus payload).
 * @method string|false decryptRTPPacket(VoicePacket $packet, int $seq = 0) Decrypt an RTP packet (header + encrypted payload).
 *
 * @link https://discord.com/developers/docs/topics/voice-connections
 * @link https://messaginglayersecurity.rocks/
 */
trait VoiceGroupCryptoTrait
{
    public string $mode;
    public int $lite_nonce = 0;
    
    /** @var int DAVE protocol version (0 = legacy mode, no MLS) */
    public int $daveProtocolVersion = 0;

    /**
     * Encrypt a message for Discord voice.
     *
     * @param string $plaintext
     * @param string $header    Optional 12-byte RTP header (for RTP-style nonce)
     * @param int    $seq       Optional sequence number
     */
    protected function encrypt(string $plaintext, string $header = '', int $seq = 0): string
    {
        $this->lite_nonce = ($this->lite_nonce + 1) & 0xFFFFFFFF;
        $header12 = str_pad(substr($header, 0, 12), 12, "\x00", STR_PAD_RIGHT);

        if ($this->mode === 'aead_aes256_gcm_rtpsize') {
            // Nonce: 8 zero bytes + 4-byte counter (BE)
            $nonce = str_repeat("\x00", 8).pack('N', $this->lite_nonce);

            try {
                $ciphertext = sodium_crypto_aead_aes256gcm_encrypt($plaintext, '', $nonce, $this->secret_key);
            } catch (\SodiumException $e) {
                $tag = '';
                $ctRaw = openssl_encrypt($plaintext, 'aes-256-gcm', $this->secret_key, OPENSSL_RAW_DATA, $nonce, $tag, '');
                if ($ctRaw === false) {
                    throw $e;
                }
                $ciphertext = $ctRaw.$tag;
            }

            return $ciphertext.pack('V', $this->lite_nonce);
        }

        if ($this->mode === 'aead_xchacha20_poly1305_rtpsize') {
            // Per Discord docs: Nonce is 12-byte RTP header + 12 zero bytes
            // https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes
            $nonce = $header12.str_repeat("\x00", 12);
            $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $this->secret_key);

            return $ciphertext.pack('V', $this->lite_nonce);
        }

        return $plaintext;
    }

    /**
     * Decrypt a message from Discord voice.
     *
     * @param string $ciphertext
     * @param string $header     Optional RTP header
     * @param int    $seq        Optional sequence number
     */
    protected function decrypt(string $ciphertext, string $header = '', int $seq = 0): string|false
    {
        if (strlen($ciphertext) < 20) {
            return false;
        }

        // Layout: CT | TAG(16) | SUFFIX(4)
        $nonceSuffix = substr($ciphertext, -4);
        $ciphertextWithTag = substr($ciphertext, 0, -4);
        $header12 = str_pad(substr($header, 0, 12), 12, "\x00", STR_PAD_RIGHT);

        // Check if DAVE/MLS is enabled
        if ($this->daveProtocolVersion > 0) {
            // DAVE mode requires full MLS implementation
            error_log(sprintf(
                'Voice decryption with DAVE protocol v%d requires MLS implementation (not yet supported). Use max_dave_protocol_version=0 for legacy mode.',
                $this->daveProtocolVersion
            ));
            return false;
        }

        // Legacy mode (DAVE disabled)
        $ctr = unpack('V', $nonceSuffix)[1];

        if ($this->mode === 'aead_xchacha20_poly1305_rtpsize') {
            // Try comprehensive nonce and AAD combinations
            $variants = [
                // Standard: header + 12 zeros
                ['nonce' => $header12.str_repeat("\x00", 12), 'aad' => '', 'label' => 'HDR+12Z-NOAAD'],
                ['nonce' => $header12.str_repeat("\x00", 12), 'aad' => $header12, 'label' => 'HDR+12Z-HDRAAD'],
                
                // With counter in nonce (BE)
                ['nonce' => $header12.str_repeat("\x00", 8).pack('N', $ctr), 'aad' => '', 'label' => 'HDR+8Z+BE-NOAAD'],
                ['nonce' => $header12.str_repeat("\x00", 8).pack('N', $ctr), 'aad' => $header12, 'label' => 'HDR+8Z+BE-HDRAAD'],
                
                // With counter in nonce (LE from suffix)
                ['nonce' => $header12.str_repeat("\x00", 8).$nonceSuffix, 'aad' => '', 'label' => 'HDR+8Z+LE-NOAAD'],
                ['nonce' => $header12.str_repeat("\x00", 8).$nonceSuffix, 'aad' => $header12, 'label' => 'HDR+8Z+LE-HDRAAD'],
                
                // Zero nonce (counter only)
                ['nonce' => str_repeat("\x00", 20).pack('N', $ctr), 'aad' => '', 'label' => '20Z+BE-NOAAD'],
                ['nonce' => str_repeat("\x00", 20).pack('N', $ctr), 'aad' => $header12, 'label' => '20Z+BE-HDRAAD'],
                ['nonce' => str_repeat("\x00", 20).$nonceSuffix, 'aad' => '', 'label' => '20Z+LE-NOAAD'],
                ['nonce' => str_repeat("\x00", 20).$nonceSuffix, 'aad' => $header12, 'label' => '20Z+LE-HDRAAD'],
                
                // All zeros
                ['nonce' => str_repeat("\x00", 24), 'aad' => '', 'label' => '24Z-NOAAD'],
                ['nonce' => str_repeat("\x00", 24), 'aad' => $header12, 'label' => '24Z-HDRAAD'],
            ];

            foreach ($variants as $variant) {
                $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertextWithTag, $variant['aad'], $variant['nonce'], $this->secret_key);
                
                if ($plaintext !== false) {
                    error_log(sprintf(
                        'XChaCha20 DECRYPT SUCCESS! Config: %s, Counter: %d, Suffix: %s',
                        $variant['label'],
                        $ctr,
                        bin2hex($nonceSuffix)
                    ));
                    return $this->stripHeaderExtension($plaintext);
                }
            }

            // All variants failed
            error_log(sprintf(
                'XChaCha20 decrypt FAILED (all %d variants). Ctr: %d, CTLen: %d, Suffix: %s, Header: %s, KeyLen: %d',
                count($variants),
                $ctr,
                strlen($ciphertextWithTag),
                bin2hex($nonceSuffix),
                bin2hex($header12),
                strlen($this->secret_key)
            ));
            
            return false;
        }

        if ($this->mode === 'aead_aes256_gcm_rtpsize') {
            // Nonce: 8 zero bytes + 4-byte counter (BE)
            $nonce = str_repeat("\x00", 8).pack('N', $ctr);

            $plaintext = false;
            try {
                $plaintext = sodium_crypto_aead_aes256gcm_decrypt($ciphertextWithTag, '', $nonce, $this->secret_key);
            } catch (\SodiumException $e) {
                // Try OpenSSL fallback
                $tag = substr($ciphertextWithTag, -16);
                $ctRaw = substr($ciphertextWithTag, 0, -16);
                $plaintext = openssl_decrypt($ctRaw, 'aes-256-gcm', $this->secret_key, OPENSSL_RAW_DATA, $nonce, $tag, '');
            }

            if ($plaintext === false) {
                return false;
            }

            return $this->stripHeaderExtension($plaintext);
        }

        return false;
    }

    /**
     * Strip RTP header extension from decrypted data.
     *
     * @param  string $data The decrypted data
     * @return string Data with header extension removed if present
     */
    protected function stripHeaderExtension(string $data): string
    {
        if (strlen($data) > 4 && ord($data[0]) === 0xBE && ord($data[1]) === 0xDE) {
            $length = unpack('n', substr($data, 2, 2))[1];
            $offset = 4 + $length * 4;
            
            if ($offset <= strlen($data)) {
                $data = substr($data, $offset);
            }
        }

        return $data;
    }

    /**
     * Encrypt an RTP packet (header + Opus payload).
     *
     * @param VoicePacket $packet
     * @param int         $seq    Sequence number for AES-GCM mode
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
        return $this->decrypt($packet->getData(), $packet->getHeader(), $seq);
    }
}
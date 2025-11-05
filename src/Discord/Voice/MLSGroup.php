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
 * A simplified implementation of an MLS (Messaging Layer Security) group for encrypting and decrypting messages among multiple members.
 *
 * Supports Optional Values and Variable-Length Vector headers according to RFC 9420.
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 *
 * @property array  $members     Array of group members with their public/private keys.
 * @property array  $tree        The ratchet tree structure for the group.
 * @property int    $nonceLength Nonce length based on encryption mode.
 * @property string $mode        The encryption mode used ('aes256-gcm-rtpsize' or 'xchacha20-poly1305-rtpsize').
 */
class MLSGroup
{
    protected array $members = []; // memberId => ['pk' => ..., 'sk' => ...]
    protected array $tree = [];    // per-member derived keys

    protected int $nonceLength; // Nonce length based on encryption mode
    protected string $mode; // 'aes256-gcm-rtpsize' or 'xchacha20-poly1305-rtpsize'

    /**
     * @param string $groupSecret Optional group secret
     * @param string $mode        'aes256-gcm-rtpsize' or 'xchacha20-poly1305-rtpsize'
     */
    public function __construct(public string $groupSecret = '', string $mode = 'xchacha20-poly1305-rtpsize')
    {
        $this->mode = strtolower($mode);

        switch ($this->mode) {
            case 'aes256-gcm-rtpsize':
                if (! defined('SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES')) {
                    throw new \RuntimeException('AES256-GCM not supported');
                }
                $this->nonceLength = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES; // 12
                $keyLen = SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES;
                break;

            case 'xchacha20-poly1305-rtpsize':
                $this->nonceLength = 24; // RTP header + 12 zero bytes
                $keyLen = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES;
                break;

            default:
                throw new \InvalidArgumentException('Unsupported mode');
        }

        if ($this->groupSecret === '') {
            $this->groupSecret = random_bytes($keyLen);
        }
    }

    /**
     * Add a member with generated key pair.
     */
    public function addMember(string $memberId): void
    {
        if (isset($this->members[$memberId])) {
            throw new \RuntimeException('Member already exists');
        }

        $keypair = sodium_crypto_kx_keypair();
        $this->members[$memberId] = [
            'pk' => sodium_crypto_kx_publickey($keypair),
            'sk' => sodium_crypto_kx_secretkey($keypair),
        ];

        $this->recomputeTree();
    }

    /**
     * Remove a member.
     */
    public function removeMember(string $memberId): void
    {
        unset($this->members[$memberId]);
        unset($this->tree[$memberId]);
    }

    /**
     * Recompute per-member derived keys from group secret.
     */
    protected function recomputeTree(): void
    {
        foreach ($this->members as $id => $member) {
            $keyLen = $this->mode === 'aes256-gcm-rtpsize'
                ? SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES
                : SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES;

            $this->tree[$id] = sodium_crypto_generichash(
                $this->groupSecret.$member['pk'],
                '',
                $keyLen
            );
        }
    }

    /**
     * Encrypt a message for a specific member.
     * @param string $memberId
     * @param string $plaintext
     * @param string $header    Optional 12-byte RTP header (for RTP-style nonce)
     * @param int    $seq       Optional sequence number (for AES-GCM)
     */
    public function encrypt(string $memberId, string $plaintext, string $header = '', int $seq = 0): string
    {
        if (! isset($this->tree[$memberId])) {
            throw new \RuntimeException('Unknown member');
        }

        $nonce = $this->buildNonce($memberId, $header, $seq);

        return match ($this->mode) {
            'aes256-gcm-rtpsize' => sodium_crypto_aead_aes256gcm_encrypt($plaintext, '', $nonce, $this->tree[$memberId]),
            'xchacha20-poly1305-rtpsize' => sodium_crypto_aead_chacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $this->tree[$memberId]),
        };
    }

    /**
     * Decrypt a message for a specific member.
     * @param string $memberId
     * @param string $ciphertext
     * @param string $header     Optional RTP header
     * @param int    $seq        Optional sequence number
     */
    public function decrypt(string $memberId, string $ciphertext, string $header = '', int $seq = 0): string
    {
        if (! isset($this->tree[$memberId])) {
            throw new \RuntimeException('Unknown member');
        }

        $nonce = $this->buildNonce($memberId, $header, $seq);

        $plaintext = match ($this->mode) {
            'aes256-gcm-rtpsize' => sodium_crypto_aead_aes256gcm_decrypt($ciphertext, '', $nonce, $this->tree[$memberId]),
            'xchacha20-poly1305-rtpsize' => sodium_crypto_aead_chacha20poly1305_ietf_decrypt($ciphertext, '', $nonce, $this->tree[$memberId]),
        };

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }

    /**
     * Build a nonce for RTP-style AEAD.
     */
    protected function buildNonce(string $memberId, string $header = '', int $seq = 0): string
    {
        return match ($this->mode) {
            'xchacha20-poly1305-rtpsize' => str_pad($header, 12, "\x00", STR_PAD_RIGHT).str_repeat("\x00", 12),
            'aes256-gcm-rtpsize' => pack('N', $seq).str_repeat("\x00", 8),
        };
    }

    public function getMemberPublicKey(string $memberId): string
    {
        return $this->members[$memberId]['pk'] ?? '';
    }

    /**
     * Encrypt an RTP packet (header + Opus payload) for a member.
     *
     * @param string $memberId
     * @param string $rtpHeader   12-byte RTP header
     * @param string $opusPayload Opus-encoded audio
     * @param int    $seq         Sequence number for AES-GCM mode
     */
    public function encryptRTPPacket(string $memberId, string $rtpHeader, string $opusPayload, int $seq = 0): string
    {
        $cipher = $this->encrypt($memberId, $opusPayload, $rtpHeader, $seq);

        return $rtpHeader.$cipher;
    }

    /**
     * Decrypt an RTP packet (header + encrypted payload) for a member.
     */
    public function decryptRTPPacket(string $memberId, string $packet, int $seq = 0): string
    {
        if (strlen($packet) < 12) {
            throw new \RuntimeException('Invalid RTP packet');
        }
        $header = substr($packet, 0, 12);
        $payloadEnc = substr($packet, 12);

        return $this->decrypt($memberId, $payloadEnc, $header, $seq);
    }
}

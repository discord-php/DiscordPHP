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
 * @property array $members Array of group members with their public/private keys.
 * @property array $tree    The ratchet tree structure for the group.
 */
class MLSGroup
{
    protected array $members = []; // memberId => ['pk' => ..., 'sk' => ...]
    protected array $tree = [];    // simple binary tree storing shared secrets

    public function __construct(protected string $groupSecret = '')
    {
        if ($this->groupSecret === '') {
            $this->groupSecret = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES);
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
        $this->recomputeTree();
    }

    /**
     * Recompute the group ratchet tree and root secret.
     *
     * @param string $secret Optional new root secret
     */
    protected function recomputeTree(string $secret = ''): void
    {
        $this->groupSecret = $secret !== '' ? $secret : $this->groupSecret;

        // Assign per-member derived secrets using generichash (message + output length)
        foreach ($this->members as $id => $member) {
            $this->tree[$id] = sodium_crypto_generichash(
                $this->groupSecret.$member['pk'], // message
                '',                                  // optional keyed hash
                SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES // output length
            );
        }
    }

    /**
     * Encrypt a message for all members.
     */
    public function encrypt(string $plaintext): array
    {
        $messages = [];
        foreach ($this->members as $id => $member) {
            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
            $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
                $plaintext,
                '', // optional AD
                $nonce,
                $this->tree[$id]
            );
            $messages[$id] = base64_encode($nonce.$ciphertext);
        }

        return $messages;
    }

    /**
     * Decrypt a message for a specific member.
     */
    public function decrypt(string $memberId, string $message): string
    {
        if (! isset($this->tree[$memberId])) {
            throw new \RuntimeException('Unknown member');
        }

        $decoded = base64_decode($message, true);
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            $ciphertext,
            '',
            $nonce,
            $this->tree[$memberId]
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }

    /**
     * Get public key of a member.
     */
    public function getMemberPublicKey(string $memberId): string
    {
        return $this->members[$memberId]['pk'] ?? '';
    }

    /**
     * Serialize an optional value (Optional<Value>).
     */
    public static function serializeOptional(mixed $value): string
    {
        return $value === null ? "\x00" : "\x01".$value;
    }

    /**
     * Deserialize an optional value (Optional<Value>).
     */
    public static function deserializeOptional(string $data, int &$offset): mixed
    {
        $flag = ord($data[$offset++]);
        if ($flag === 0) {
            return null;
        }
        $value = substr($data, $offset); // placeholder: adjust based on expected value
        $offset += strlen($value);

        return $value;
    }

    /**
     * Encode a variable-length vector according to RFC 9420.
     */
    public static function encodeVector(string $data): string
    {
        $len = strlen($data);
        if ($len <= 0xff) {
            return chr($len).$data;
        } elseif ($len <= 0xffff) {
            return pack('n', $len).$data;
        } elseif ($len <= 0xffffff) {
            $b1 = ($len >> 16) & 0xff;
            $b2 = ($len >> 8) & 0xff;
            $b3 = $len & 0xff;

            return chr($b1).chr($b2).chr($b3).$data;
        }
        throw new \RuntimeException('Vector too large');
    }

    /**
     * Decode a variable-length vector according to RFC 9420.
     */
    public static function decodeVector(string $data, int &$offset, int $lengthBytes): string
    {
        if ($lengthBytes === 1) {
            $len = ord($data[$offset]);
        } elseif ($lengthBytes === 2) {
            $len = unpack('n', substr($data, $offset, 2))[1];
        } elseif ($lengthBytes === 3) {
            $b = unpack('C3', substr($data, $offset, 3));
            $len = ($b[1] << 16) | ($b[2] << 8) | $b[3];
        } else {
            throw new \RuntimeException('Invalid lengthBytes');
        }
        $offset += $lengthBytes;
        $vector = substr($data, $offset, $len);
        $offset += $len;

        return $vector;
    }
}

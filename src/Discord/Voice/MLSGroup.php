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
 * @todo
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
        // For demonstration, root secret = random bytes
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
     * Recompute the group ratchet tree and root secret
     * Simplified: generate a random root secret.
     *
     * In a real MLS implementation, this would involve complex tree operations
     * and key derivations to ensure forward secrecy and post-compromise security.
     *
     * @param string $secret Optional new root secret
     */
    protected function recomputeTree(string $secret = ''): void
    {
        // For demonstration, root secret = random bytes
        $this->groupSecret = $secret !== '' ? $secret : (isset($this->groupSecret) ? $this->groupSecret : random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES));

        // Assign per-member shared secrets (simulate ratchet)
        foreach ($this->members as $id => $member) {
            $this->tree[$id] = sodium_crypto_generichash($this->groupSecret.$member['pk'], length: SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES);
        }
    }

    /**
     * Encrypt message for the whole group.
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
     * Decrypt message for a specific member.
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
}

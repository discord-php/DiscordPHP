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

namespace Discord\Voice;

/**
 * Interface for group-based AEAD encryption and decryption for Discord voice RTP packets.
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 *
 * @link https://docs.discord.com/developers/topics/voice-connections#transport-encryption-and-sending-voice
 *
 * @since 10.41.0
 */
interface VoiceGroupCryptoInterface
{
    /**
     * Encrypts the payload of an RTP `$packet` for the current voice group.
     *
     * @param VoicePacket $packet
     * @param int         $seq    RTP sequence number, mixed into the nonce.
     *
     * @return string The encrypted packet bytes.
     */
    public function encryptRTPPacket(VoicePacket $packet, int $seq = 0): string;

    /**
     * Decrypts the payload of an RTP `$packet` for the current voice group.
     *
     * @param VoicePacket $packet
     * @param int         $seq    RTP sequence number, mixed into the nonce.
     *
     * @return string|false The decrypted bytes, or false on authentication failure.
     */
    public function decryptRTPPacket(VoicePacket $packet, int $seq = 0): string|false;
}

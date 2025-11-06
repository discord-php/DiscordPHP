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
 * Interface for group-based AEAD encryption and decryption for Discord voice RTP packets.
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 * 
 * @link https://discord.com/developers/docs/topics/voice-connections#transport-encryption-and-sending-voice
 *
 * @since 10.41.0
 */
interface VoiceGroupCryptoInterface
{
    public function encryptRTPPacket(VoicePacket $packet, int $seq = 0): string;
    public function decryptRTPPacket(VoicePacket $packet, int $seq = 0): string|false;
}

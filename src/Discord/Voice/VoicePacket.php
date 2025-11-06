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
 * A voice packet received from Discord.
 *
 * Huge thanks to Austin and Michael from JDA for the constants and audio
 * packets. Check out their repo:
 * https://github.com/discord-jda/JDA
 *
 * @since 3.2.0
 */
class VoicePacket
{
    public const RTP_HEADER_BYTE_LENGTH = 12;
    public const AUTH_TAG_LENGTH = 16;

    /**
     * Bit index 0 and 1 represent the RTP Protocol version used. Discord uses the latest RTP protocol version, 2.
     * Bit index 2 represents whether or not we pad. Opus uses an internal padding system, so RTP padding is not used.
     * Bit index 3 represents if we use extensions.
     * Bit index 4 to 7 represent the CC or CSRC count. CSRC is Combined SSRC.
     */
    public const RTP_VERSION_PAD_EXTEND = 0x80;
    /**
     * This is Discord's RTP Profile Payload type,
     * which is the same as Opus audio RTP stream's default payload type of 120 (0x78 & 0x7F).
     *
     * @link https://www.opus-codec.org/docs/opus-tools/opusrtp.html
     * @link https://datatracker.ietf.org/doc/html/rfc3551
     */
    public const RTP_PAYLOAD_TYPE = 0x78;

    public const RTP_VERSION_PAD_EXTEND_INDEX = 0;
    public const RTP_PAYLOAD_INDEX = 1;
    public const SEQ_INDEX = 2;
    public const TIMESTAMP_INDEX = 4;
    public const SSRC_INDEX = 8;

    /**
     * The voice packet buffer.
     *
     * @var Buffer
     */
    protected $buffer;

    /**
     * The client SSRC.
     *
     * @var int The client SSRC.
     */
    protected $ssrc;

    /**
     * The packet sequence.
     *
     * @var int The packet sequence.
     */
    protected $seq;

    /**
     * The packet timestamp.
     *
     * @var float The packet timestamp.
     */
    protected $timestamp;

    /**
     * Constructs the voice packet.
     *
     * @param string      $data       The Opus data to encode.
     * @param int         $ssrc       The client SSRC value.
     * @param int         $seq        The packet sequence.
     * @param float       $timestamp  The packet timestamp.
     * @param bool        $encryption (Deprecated) Whether the packet should be encrypted.
     * @param string|null $key        (Deprecated) The encryption key.
     */
    public function __construct(string $data, int $ssrc, int $seq, float $timestamp, bool $encryption = false, ?string $key = null)
    {
        $this->ssrc = $ssrc;
        $this->seq = $seq;
        $this->timestamp = $timestamp;

        if (! $encryption) {
            $this->initBufferNoEncryption($data);
        } else {
            $this->initBufferEncryption($data, $key);
        }
    }

    /**
     * Validates a VoicePacket for sending.
     *
     * @param VoicePacket $packet The packet to validate.
     *
     * @return bool Whether the packet is valid.
     */
    public static function validatePacket(VoicePacket $packet): bool
    {
        // RTP header must be 12 bytes
        $header = $packet->getHeader();
        if (strlen($header) !== VoicePacket::RTP_HEADER_BYTE_LENGTH) {
            return false;
        }

        // Check RTP version and payload type
        $unpacked = unpack('Cversion/Cpayload', $header);
        if (($unpacked['version'] & 0xC0) !== 0x80) { // Version 2
            return false;
        }
        if ($unpacked['payload'] !== VoicePacket::RTP_PAYLOAD_TYPE) {
            return false;
        }

        // Sequence: 0–65535
        $seq = $packet->getSequence();
        if ($seq < 0 || $seq > 0xFFFF) {
            return false;
        }

        // Timestamp: 0–4294967295
        $timestamp = $packet->getTimestamp();
        if ($timestamp < 0 || $timestamp > 0xFFFFFFFF) {
            return false;
        }

        // SSRC: non-zero
        $ssrc = $packet->getSSRC();
        if ($ssrc === 0) {
            return false;
        }

        // Opus payload: not empty, reasonable size
        $data = $packet->getData();
        if (empty($data) || strlen($data) < 10 || strlen($data) > 400) {
            return false;
        }

        return true;
    }

    /**
     * Initilizes the buffer with no encryption.
     *
     * @param string $data The Opus data to encode.
     */
    protected function initBufferNoEncryption(string $data): void
    {
        $packet = (string) $this->buildHeader().$data;

        $this->buffer = new Buffer(strlen($packet));
        $this->buffer->write($packet, 0);
    }

    /**
     * Initilizes the buffer with encryption.
     *
     * @param string $data The Opus data to encode.
     * @param string $key  The encryption key.
     *
     * @deprecated v10.41.0 Use `VoiceGroupCrypto::encryptRTPPacket()`
     */
    protected function initBufferEncryption(string $data, string $key): void
    {
        $header = (string) $this->buildHeader();
        $encrypted = \sodium_crypto_secretbox($data, str_pad($header, 24, "\0"), $key);

        $this->buffer = new Buffer(strlen($header) + strlen($encrypted));
        $this->buffer->write($header.$encrypted, 0);
    }

    /**
     * Builds the header.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes-voice-packet-structure
     *
     * @return Buffer The header.
     */
    protected function buildHeader(): Buffer
    {
        $header = new Buffer(self::RTP_HEADER_BYTE_LENGTH);

        $header->write(pack(
            'CCnNN',
            self::RTP_VERSION_PAD_EXTEND,
            self::RTP_PAYLOAD_TYPE,
            $this->seq,
            $this->timestamp,
            $this->ssrc
        ), 0);

        return $header;
    }

    /**
     * Returns the sequence.
     *
     * @return int The packet sequence.
     */
    public function getSequence(): int
    {
        return $this->seq;
    }

    /**
     * Returns the timestamp.
     *
     * @return float The packet timestamp.
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Returns the SSRC.
     *
     * @return int The packet SSRC.
     */
    public function getSSRC(): int
    {
        return $this->ssrc;
    }

    /**
     * Returns the header.
     *
     * @return string The packet header.
     */
    public function getHeader(): string
    {
        return $this->buffer->read(0, self::RTP_HEADER_BYTE_LENGTH);
    }

    /**
     * Returns the data.
     *
     * @return string The packet data.
     */
    public function getData(): string
    {
        return $this->buffer->read(self::RTP_HEADER_BYTE_LENGTH, strlen((string) $this->buffer) - self::RTP_HEADER_BYTE_LENGTH);
    }

    /**
     * Creates a voice packet from data sent from Discord.
     *
     * @param string $data Data from Discord.
     *
     * @return VoicePacket A voice packet.
     */
    public static function make(string $data): VoicePacket
    {
        $n = new self('', 0, 0, 0);

        $n->setBuffer(new Buffer($data));

        return $n;
    }

    /**
     * Sets the buffer.
     *
     * @param Buffer $buffer The buffer to set.
     *
     * @return $this
     */
    public function setBuffer(Buffer $buffer): self
    {
        $this->buffer = $buffer;

        $this->seq = $this->buffer->readShort(self::SEQ_INDEX);
        $this->timestamp = (float) $this->buffer->readInt(self::TIMESTAMP_INDEX);
        $this->ssrc = $this->buffer->readInt(self::SSRC_INDEX);

        return $this;
    }

    /**
     * Handles to string casting of object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->buffer;
    }
}

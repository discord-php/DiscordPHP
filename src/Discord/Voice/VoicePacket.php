<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

/**
 * A voice packet received from Discord.
 */
class VoicePacket
{
    /**
     * Huge thanks to Austin and Michael from JDA for these constants
     * and audio packets.
     *
     * Check out their repo:
     * https://github.com/DV8FromTheWorld/JDA
     */
    const RTP_HEADER_BYTE_LENGTH = 12;

    const RTP_VERSION_PAD_EXTEND_INDEX = 0;
    const RTP_VERSION_PAD_EXTEND       = 0x80;

    const RTP_PAYLOAD_INDEX = 1;
    const RTP_PAYLOAD_TYPE  = 0x78;

    const SEQ_INDEX       = 2;
    const TIMESTAMP_INDEX = 4;
    const SSRC_INDEX      = 8;

    /**
     * The voice packet buffer.
     *
     * @var \Discord\Voice\Buffer
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
     * @var int The packet timestamp.
     */
    protected $timestamp;

    /**
     * Constructs the voice packet.
     *
     * @param string      $data       The Opus data to encode.
     * @param int         $ssrc       The client SSRC value.
     * @param int         $seq        The packet sequence.
     * @param int         $timestamp  The packet timestamp.
     * @param bool        $encryption Whether the packet should be encrypted.
     * @param string|null $key        The encryption key.
     *
     * @return void
     */
    public function __construct($data, $ssrc, $seq, $timestamp, $encryption = false, $key = null)
    {
        $this->ssrc      = $ssrc;
        $this->seq       = $seq;
        $this->timestamp = $timestamp;

        if (! $encryption) {
            $this->initBufferNoEncryption($data);
        } else {
            $this->initBufferEncryption($data, $key);
        }
    }

    /**
     * Initilizes the buffer with no encryption.
     *
     * @param string $data The Opus data to encode.
     *
     * @return void
     */
    protected function initBufferNoEncryption($data)
    {
        $data   = (binary) $data;
        $header = $this->buildHeader();

        $buffer = new Buffer(strlen((string) $header) + strlen($data));
        $buffer->write((string) $header, 0);
        $buffer->write($data, 12);

        $this->buffer = $buffer;
    }

    /**
     * Initilizes the buffer with encryption.
     *
     * @param string $data The Opus data to encode.
     * @param string $key  The encryption key.
     *
     * @return void
     */
    protected function initBufferEncryption($data, $key)
    {
        $data   = (binary) $data;
        $header = $this->buildHeader();
        $nonce  = new Buffer(24);
        $nonce->write((string) $header, 0);

        $data = \Sodium\crypto_secretbox($data, (string) $nonce, $key);

        $this->buffer = new Buffer(strlen((string) $header) + strlen($data));
        $this->buffer->write((string) $header, 0);
        $this->buffer->write($data, 12);
    }

    /**
     * Builds the header.
     *
     * @return string The header,
     */
    protected function buildHeader()
    {
        $header                                     = new Buffer(self::RTP_HEADER_BYTE_LENGTH);
        $header[self::RTP_VERSION_PAD_EXTEND_INDEX] = pack('c', self::RTP_VERSION_PAD_EXTEND);
        $header[self::RTP_PAYLOAD_INDEX]            = pack('c', self::RTP_PAYLOAD_TYPE);
        $header->writeShort($this->seq, self::SEQ_INDEX);
        $header->writeInt($this->timestamp, self::TIMESTAMP_INDEX);
        $header->writeInt($this->ssrc, self::SSRC_INDEX);

        return $header;
    }

    /**
     * Returns the sequence.
     *
     * @return int The packet sequence.
     */
    public function getSequence()
    {
        return $this->seq;
    }

    /**
     * Returns the timestamp.
     *
     * @return int The packet timestamp.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Returns the SSRC.
     *
     * @return int The packet SSRC.
     */
    public function getSSRC()
    {
        return $this->ssrc;
    }

    /**
     * Returns the header.
     *
     * @return string The packet header.
     */
    public function getHeader()
    {
        return $this->buffer->read(0, self::RTP_HEADER_BYTE_LENGTH);
    }

    /**
     * Returns the data.
     *
     * @return string The packet data.
     */
    public function getData()
    {
        return $this->buffer->read(self::RTP_HEADER_BYTE_LENGTH, strlen((string) $this->buffer) - self::RTP_HEADER_BYTE_LENGTH);
    }

    /**
     * Creates a voice packet from data sent from Discord.
     *
     * @param string $data Data from Discord.
     *
     * @return self A voice packet.
     */
    public static function make($data)
    {
        $n    = new self('', 0, 0, 0);
        $buff = new Buffer($data);
        $n->setBuffer($buff);

        return $n;
    }

    /**
     * Sets the buffer.
     *
     * @param Buffer $buffer The buffer to set.
     *
     * @return self
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;

        $this->seq       = $this->buffer->readShort(self::SEQ_INDEX);
        $this->timestamp = $this->buffer->readInt(self::TIMESTAMP_INDEX);
        $this->ssrc      = $this->buffer->readInt(self::SSRC_INDEX);

        return $this;
    }

    /**
     * Handles to string casting of object.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->buffer;
    }
}

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
    const RTP_VERSION_PAD_EXTEND = 0x80;

    const RTP_PAYLOAD_INDEX = 1;
    const RTP_PAYLOAD_TYPE = 0x78;

    const SEQ_INDEX = 2;
    const TIMESTAMP_INDEX = 4;
    const SSRC_INDEX = 8;

    /**
     * The voice packet buffer.
     *
     * @var \Discord\Voice\Buffer
     */
    protected $buffer;

    /**
     * Constructs the voice packet.
     *
     * @param opus $opusdata
     * @param int  $ssrc
     * @param int  $seq
     * @param int  $timestamp
     *
     * @return void
     */
    public function __construct($opusdata, $ssrc, $seq, $timestamp)
    {
        $this->initBuffer($opusdata, $ssrc, $seq, $timestamp);
    }

    /**
     * Initilizes the buffer.
     *
     * @param opus $opusdata
     * @param int  $ssrc
     * @param int  $seq
     * @param int  $timestamp
     *
     * @return void
     */
    public function initBuffer($opusdata, $ssrc, $seq, $timestamp)
    {
        $data = (binary) $opusdata;

        $buffer = new Buffer(strlen($opusdata) + self::RTP_HEADER_BYTE_LENGTH);
        $buffer[self::RTP_VERSION_PAD_EXTEND_INDEX] = pack('c', self::RTP_VERSION_PAD_EXTEND);
        $buffer[self::RTP_PAYLOAD_INDEX] = pack('c', self::RTP_PAYLOAD_TYPE);
        $buffer->writeShort($seq, self::SEQ_INDEX);
        $buffer->writeInt($timestamp, self::TIMESTAMP_INDEX);
        $buffer->writeInt($ssrc, self::SSRC_INDEX);

        for ($i = 0; $i < strlen($data); ++$i) {
            $buffer[self::RTP_HEADER_BYTE_LENGTH + $i] = $data[$i];
        }

        $this->buffer = $buffer;
    }

    /**
     * Returns the sequence.
     *
     * @return int The packet sequence.
     */
    public function getSequence()
    {
        return $this->buffer->readShort(self::SEQ_INDEX);
    }

    /**
     * Returns the timestamp.
     *
     * @return int The packet timestamp.
     */
    public function getTimestamp()
    {
        return $this->buffer->readInt(self::TIMESTAMP_INDEX);
    }

    /**
     * Returns the SSRC.
     *
     * @return int The packet SSRC.
     */
    public function getSSRC()
    {
        return $this->buffer->readInt(self::SSRC_INDEX);
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
        $n = new self('', 0, 0, 0);
        $n->setBuffer(new Buffer($data));

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

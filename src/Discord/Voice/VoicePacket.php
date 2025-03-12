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

use Monolog\Logger;
use TrafficCophp\ByteBuffer\FormatPackEnum;

/**
 * A voice packet received from Discord.
 *
 * Huge thanks to Austin and Michael from JDA for the constants and audio
 * packets. Check out their repo:
 * https://github.com/DV8FromTheWorld/JDA
 *
 * @since 3.2.0
 */
class VoicePacket
{

    # RTP Header Constants
    public const RTP_HEADER_BYTE_LENGTH = 12;

    public const RTP_VERSION_PAD_EXTEND_INDEX = 0;

    public const RTP_VERSION_PAD_EXTEND = 0x80;

    public const RTP_PAYLOAD_INDEX = 1;

    public const RTP_PAYLOAD_TYPE = 0x78;

    public const SEQ_INDEX = 2;

    public const TIMESTAMP_INDEX = 4;

    public const SSRC_INDEX = 8;

    public const NONCE_LENGTH = 12;

    public const NONCE_BYTE_LENGTH = 4;

    public const AUTH_TAG_LENGTH = 16;

    /**
     * The audio header, in binary, containing the version, flags, sequence, timestamp, and SSRC.
     *
     * @var string
     */
    protected string $header;

    /**
     * The buffer containing the voice packet.
     *
     * @deprecated
     *
     * @var Buffer
     */
    protected $buffer;

    /**
     * The client SSRC.
     *
     * @var int The client SSRC.
     */
    public ?int $ssrc;

    /**
     * The packet sequence.
     *
     * @var int The packet sequence.
     */
    public ?int $seq;

    /**
     * The packet timestamp.
     *
     * @var int The packet timestamp.
     */
    public ?int $timestamp;

    /**
     * The version and flags.
     *
     * @var string The version and flags.
     */
    public ?string $versionPlusFlags;

    /**
     * The payload type.
     *
     * @var string The payload type.
     */
    public ?string $payloadType;

    /**
     * The encrypted audio.
     *
     * @var string The encrypted audio.
     */
    public ?string $encryptedAudio;

    /**
     * The dencrypted audio.
     *
     * @var string
     */
    public null|string|false $decryptedAudio;

    /**
     * The secret key.
     *
     * @var string The secret key.
     */
    public ?string $secretKey;

    /**
     * The raw data
     *
     * @var string
     */
    private string $rawData;

    /**
     * Current packet header size. May differ depending on the RTP header.
     *
     * @var int
     */
    private int $headerSize;

    /**
     * Constructs the voice packet.
     *
     * @param string      $data       The Opus data to encode.
     * @param int         $ssrc       The client SSRC value.
     * @param int         $seq        The packet sequence.
     * @param int         $timestamp  The packet timestamp.
     * @param bool        $encryption Whether the packet should be encrypted.
     * @param string|null $key        The encryption key.
     */
    public function __construct(?string $data = null, ?int $ssrc = null, ?int $seq = null, ?int $timestamp = null, bool $encryption = false, private ?string $key = null, private ?Logger $log = null)
    {
        $this->unpack($data)
            ->decrypt();
    }

    /**
     * Unpacks the voice message into an array.
     *
     * C1 (unsigned char)                       | Version + Flags       | 1 bytes | Single byte value of 0x80
     * C1 (unsigned char)                       | Payload Type          | 1 bytes | Single byte value of 0x78
     * n (Unsigned short (big endian))          | Sequence              | 2 bytes
     * I (Unsigned integer (big endian))        | Timestamp             | 4 bytes
     * I (Unsigned integer (big endian))        | SSRC                  | 4 bytes
     * a* (string)                              | Encrypted audio       | n bytes | Binary data of the encrypted audio.
     *
     * @see https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes-voice-packet-structure
     * @see https://www.php.net/manual/en/function.unpack.php
     * @see https://www.php.net/manual/en/function.pack.php For the formats
     */
    public function unpack(string $message): self
    {
        $byteHeader = $this->setHeader($message);

        if (! $byteHeader) {
            $this->log->warning('Failed to unpack voice packet Header.', ['message' => $message]);
            echo 'Failed to unpack voice packet Header.' . PHP_EOL;
            return $this;
        }

        $byteData = substr(
            $message,
            self::RTP_HEADER_BYTE_LENGTH,
            strlen($message) - self::AUTH_TAG_LENGTH - self::NONCE_LENGTH
        );

        $unpackedMessage = unpack('Cfirst/Csecond/nseq/Ntimestamp/Nssrc', $byteHeader);

        if (! $unpackedMessage) {
            $this->log->warning('Failed to unpack voice packet.', ['message' => $message]);
            return $this;
        }

        $this->rawData = $message;
        $this->header = $byteHeader;
        $this->encryptedAudio = $byteData;

        $this->ssrc = $unpackedMessage['ssrc'];
        $this->seq = $unpackedMessage['seq'];
        $this->timestamp = $unpackedMessage['timestamp'];
        $this->payloadType = $unpackedMessage['payload_type'] ?? null;
        $this->versionPlusFlags = $unpackedMessage['version_and_flags'] ?? null;

        return $this;
    }

    /**
     * Decrypts the voice message.
     *
     * @param string|null $message The message to decrypt.
     *
     * @return false|null|string
     */
    public function decrypt(?string $message = null): false|null|string
    {
        if (! $message) {
            $message = $this?->rawData ?? null;
        }

        if (empty($message)) {
            // throw error here
            return null;
        }

        $len = strlen($message);

        // 2. Extract the header
        $header = $this->getHeader();
        if (! $header) {
            $this->log->warning('Invalid Voice Header.', ['message' => $message]);
            return false;
        }

        // 3. Extract the nonce
        $nonce = substr($message, $len - self::NONCE_BYTE_LENGTH, self::NONCE_BYTE_LENGTH);
        // 4. Pad the nonce to 12 bytes
        $nonceBuffer = str_pad($nonce, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES, "\0", STR_PAD_RIGHT);

        // 5. Extract the ciphertext and auth tag
        //    The message: [header][ciphertext][auth tag][nonce]
        //    The size of the ciphertext is: total - headerSize - 16 (auth tag) - 4 (nonce)
        $encryptedLength = $len - $this->headerSize - self::AUTH_TAG_LENGTH - self::NONCE_BYTE_LENGTH;
        $cipherText = substr($message, $this->headerSize, $encryptedLength);
        $authTag = substr($message, $this->headerSize + $encryptedLength, self::AUTH_TAG_LENGTH);

        // Concatenate the ciphertext and the auth tag
        $combined = "$cipherText$authTag";

        $resultMessage = null;

        try {
            // Decrypt the message
            $resultMessage = sodium_crypto_aead_aes256gcm_decrypt(
                $combined,
                $header,
                $nonceBuffer,
                $this->key
            );

            // If decryption fails, log the error and return
            // Most of the time, the length is 20 bytes either for a ping, or an empty voice/udp packet
            if ($resultMessage === false && strlen($cipherText) !== 20) {
                $this->log->warning('Failed to decode voice packet.', ['ssrc' => $this->ssrc]);
            }
            // Check if the message contains an extension and remove it
            elseif (substr($message, 12, 2) === "\xBE\xDE") {
                // Reads the 2 bytes after the extension identifier to get the extension length
                $extLengthData = substr($message, 14, 2);
                $headerExtensionLength = unpack('n', $extLengthData)[1];

                // Remove 4 * headerExtensionLength bytes from the beginning of the decrypted result
                $resultMessage = substr($resultMessage, 4 * $headerExtensionLength);
            }
        } catch (\Throwable $e) {
            $this->log->error('Exception occurred when decoding voice packet: ' . $e->getMessage());
            $this->log->error('Trace: ' . $e->getTraceAsString());
        } finally {
            return $this->decryptedAudio = $resultMessage;
        }
    }

    /**
     * Initilizes the buffer with no encryption.
     *
     * @deprecated
     *
     * @param string $data The Opus data to encode.
     */
    protected function initBufferNoEncryption(string $data): void
    {
        $data = (string) $data;
        $header = $this->buildHeader();

        $this->buffer = Buffer::make(strlen((string) $header) + strlen($data))
            ->write((string) $header, 0)
            ->write($data, 12);
    }

    /**
     * Initilizes the buffer with encryption.
     *
     * @param string $data The Opus data to encode.
     * @param string $key  The encryption key.
     */
    protected function initBufferEncryption(string $data, string $key): void
    {
        $data = (string) $data;
        $header = $this->buildHeader();
        $nonce = new Buffer(24);
        $nonce->write((string) $header, 0);

        $data = \sodium_crypto_secretbox($data, (string) $nonce, $key);

        $this->buffer = new Buffer(strlen((string) $header) + strlen($data));
        $this->buffer->write((string) $header, 0);
        $this->buffer->write($data, 12);
    }

    /**
     * Builds the header.
     *
     * @return Buffer The header.
     */
    protected function buildHeader(): Buffer
    {
        $header = new Buffer(self::RTP_HEADER_BYTE_LENGTH);
        $header[self::RTP_VERSION_PAD_EXTEND_INDEX] = pack(FormatPackEnum::C->value, self::RTP_VERSION_PAD_EXTEND);
        $header[self::RTP_PAYLOAD_INDEX] = pack(FormatPackEnum::C->value, self::RTP_PAYLOAD_TYPE);
        return $header->writeShort($this->seq, self::SEQ_INDEX)
            ->writeUInt($this->timestamp, self::TIMESTAMP_INDEX)
            ->writeUInt($this->ssrc, self::SSRC_INDEX);
    }

    public function setHeader(?string $message = null): ?string
    {
        if (null === $message) {
            $message = $this?->rawData;
        }

        if (empty($message)) {
            // throw error here
            return null;
        }

        $this->headerSize = self::RTP_HEADER_BYTE_LENGTH;
        $firstByte = ord($message[0]);
        if (($firstByte >> 4) & 0x01) {
            $this->headerSize += 4;
        }

        return substr($message, 0, $this->headerSize);
    }

    public function getHeader(): ?string
    {
        return $this?->header ?? null;
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
     * @return int The packet timestamp.
     */
    public function getTimestamp(): int
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
        $buff = new Buffer($data);
        $n->setBuffer($buff);
        unset($buff);

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
        $this->timestamp = $this->buffer->readUInt(self::TIMESTAMP_INDEX);
        $this->ssrc = $this->buffer->readUInt(self::SSRC_INDEX);

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

    /**
     * Retrieves the decrypted audio data.
     * Will return null if the audio data is not decrypted and false on error.
     *
     * @return null|string|false
     */
    public function getAudioData(): null|string|false
    {
        return $this?->decryptedAudio;
    }
}

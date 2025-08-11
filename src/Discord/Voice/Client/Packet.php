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

namespace Discord\Voice\Client;

use Discord\Exceptions\LibSodiumNotFoundException;
use Discord\Helpers\ByteBuffer\Buffer;
use Discord\Helpers\FormatPackEnum;
use Monolog\Logger;

use function Discord\logger;

/**
 * A voice packet received from Discord.
 *
 * Huge thanks to Austin and Michael from JDA for the constants and audio
 * packets. Check out their repo:
 * https://github.com/DV8FromTheWorld/JDA
 *
 * @since 10.19.0
 */
final class Packet
{
    /**
     * The audio header, in binary, containing the version, flags, sequence, timestamp, and SSRC.
     */
    protected string $header;

    /**
     * The buffer containing the voice packet.
     *
     * @deprecated
     */
    protected Buffer $buffer;

    /**
     * The version and flags.
     */
    public ?string $versionPlusFlags;

    /**
     * The payload type.
     */
    public ?string $payloadType;

    /**
     * The encrypted audio.
     */
    public ?string $encryptedAudio;

    /**
     * The dencrypted audio.
     */
    public null|false|string $decryptedAudio;

    /**
     * The secret key.
     */
    public ?string $secretKey;

    /**
     * The raw data
     */
    protected string $rawData;

    /**
     * Current packet header size. May differ depending on the RTP header.
     */
    protected int $headerSize;

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
    public function __construct(
        ?string $data = null,
        public ?int $ssrc = null,
        public ?int $seq = null,
        public ?int $timestamp = null,
        bool $decrypt = true,
        protected ?string $key = null,
        protected ?Logger $log = null
    ) {
        if (! function_exists('sodium_crypto_secretbox')) {
            throw new LibSodiumNotFoundException('libsodium-php could not be found.');
        }

        $this->unpack($data);

        if ($decrypt) {
            $this->decrypt();
        }

        if (! $log) {
            $this->log = logger();
        }
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
            return $this;
        }

        $byteData = substr(
            $message,
            HeaderValuesEnum::RTP_HEADER_OR_NONCE_LENGTH->value,
            strlen($message) - HeaderValuesEnum::AUTH_TAG_LENGTH->value - HeaderValuesEnum::RTP_HEADER_OR_NONCE_LENGTH->value
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
     */
    public function decrypt(?string $message = null): string|false|null
    {
        if (! $message) {
            $message = $this?->rawData ?? null;
        }

        if (empty($message)) {
            // throw error here
            return null;
        }

        // total message length
        $len = strlen($message);

        // 2. Extract the header
        $header = $this->getHeader();
        if (! $header) {
            $this->log->warning('Invalid Voice Header.', ['message' => $message]);
            return false;
        }

        // 3. Extract the nonce
        $nonce = substr($message, $len - HeaderValuesEnum::TIMESTAMP_OR_NONCE_INDEX->value, HeaderValuesEnum::TIMESTAMP_OR_NONCE_INDEX->value);
        // 4. Pad the nonce to 12 bytes
        $nonceBuffer = str_pad($nonce, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES, "\0", STR_PAD_RIGHT);

        // 5. Extract the ciphertext and auth tag
        //    The message: [header][ciphertext][auth tag][nonce]
        //    The size of the ciphertext is: total - headerSize - 16 (auth tag) - 4 (nonce)
        $encryptedLength = $len - $this->headerSize - HeaderValuesEnum::AUTH_TAG_LENGTH->value - HeaderValuesEnum::TIMESTAMP_OR_NONCE_INDEX->value;
        $cipherText = substr($message, $this->headerSize, $encryptedLength);
        $authTag = substr($message, $this->headerSize + $encryptedLength, HeaderValuesEnum::AUTH_TAG_LENGTH->value);

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
     */
    protected function buildHeader(): Buffer
    {
        $header = new Buffer(HeaderValuesEnum::RTP_HEADER_OR_NONCE_LENGTH->value);
        $header[HeaderValuesEnum::RTP_VERSION_PAD_EXTEND_INDEX->value] = pack(FormatPackEnum::C->value, HeaderValuesEnum::RTP_VERSION_PAD_EXTEND->value);
        $header[HeaderValuesEnum::RTP_PAYLOAD_INDEX->value] = pack(FormatPackEnum::C->value, HeaderValuesEnum::RTP_PAYLOAD_TYPE->value);
        return $header->writeShort($this->seq, HeaderValuesEnum::SEQ_INDEX->value)
            ->writeUInt($this->timestamp, HeaderValuesEnum::TIMESTAMP_OR_NONCE_INDEX->value)
            ->writeUInt($this->ssrc, HeaderValuesEnum::SSRC_INDEX->value);
    }

    /**
     * Sets the header.
     * If no message is provided, it will use the raw data of the packet.
     */
    public function setHeader(?string $message = null): ?string
    {
        if (null === $message) {
            $message = $this?->rawData;
        }

        if (empty($message)) {
            // throw error here
            return null;
        }

        $this->headerSize = HeaderValuesEnum::RTP_HEADER_OR_NONCE_LENGTH->value;
        $firstByte = ord($message[0]);
        if (($firstByte >> 4) & 0x01) {
            $this->headerSize += 4;
        }

        return substr($message, 0, $this->headerSize);
    }

    /**
     * Returns the header.
     */
    public function getHeader(): ?string
    {
        return $this?->header ?? null;
    }

    /**
     * Returns the sequence.
     */
    public function getSequence(): int
    {
        return $this->seq;
    }

    /**
     * Returns the timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Returns the SSRC.
     */
    public function getSSRC(): int
    {
        return $this->ssrc;
    }

    /**
     * Returns the data.
     */
    public function getData(): string
    {
        return $this->buffer->read(
            HeaderValuesEnum::RTP_HEADER_OR_NONCE_LENGTH->value,
            strlen((string) $this->buffer) - HeaderValuesEnum::RTP_HEADER_OR_NONCE_LENGTH->value
        );
    }

    /**
     * Creates a voice packet from data sent from Discord.
     */
    public static function make(string $data): self
    {
        $n = new self('', 0, 0, 0);
        $buff = new Buffer($data);
        $n->setBuffer($buff);
        unset($buff);

        return $n;
    }

    /**
     * Sets the buffer.
     */
    public function setBuffer(Buffer $buffer): self
    {
        $this->buffer = $buffer;

        $this->seq = $this->buffer->readShort(HeaderValuesEnum::SEQ_INDEX->value);
        $this->timestamp = $this->buffer->readUInt(HeaderValuesEnum::TIMESTAMP_OR_NONCE_INDEX->value);
        $this->ssrc = $this->buffer->readUInt(HeaderValuesEnum::SSRC_INDEX->value);

        return $this;
    }

    /**
     * Handles to string casting of object.
     */
    public function __toString(): string
    {
        return (string) $this?->buffer ?? $this->decryptedAudio ?? '';
    }

    /**
     * Retrieves the decrypted audio data.
     * Will return null if the audio data is not decrypted and false on error.
     */
    public function getAudioData(): string|false|null
    {
        return $this?->decryptedAudio;
    }
}

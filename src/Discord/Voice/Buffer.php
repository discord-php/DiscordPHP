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

use ArrayAccess;
use Discord\Helpers\FormatPackEnum;
use SplFixedArray;
use TrafficCophp\ByteBuffer\Buffer as BaseBuffer;

/**
 * A Byte Buffer similar to Buffer in NodeJS.
 *
 * @since 3.2.0
 */
class Buffer extends BaseBuffer implements ArrayAccess
{
    public function __construct($argument)
    {
        match (true) {
            is_string($argument) => $this->initializeStructs(strlen($argument), $argument),
            is_int($argument) => $this->initializeStructs($argument, pack(FormatPackEnum::x->value . "$argument")),
            default => throw new \InvalidArgumentException('Constructor argument must be an binary string or integer')
        };
    }

    public function __toString(): string
    {
        $buf = '';
        foreach ($this->buffer as $bytes) {
            $buf .= $bytes;
        }
        return $buf;
    }

    public static function make($argument): static
    {
        return new static($argument);
    }

    protected function initializeStructs($length, $content): void
    {
        $this->buffer = new SplFixedArray($length);
        for ($i = 0; $i < $length; $i++) {
            $this->buffer[$i] = $content[$i];
        }
    }

    /**
     * Inserts a value into the buffer at the specified offset.
     *
     * @param FormatPackEnum|string $format
     * @param mixed $value
     * @param int $offset
     * @param mixed $length
     * @return Buffer
     */
    protected function insert($format, $value, $offset, $length): self
    {
        $bytes = pack($format?->value ?? $format, $value);

        if (null === $length) {
            $length = strlen($bytes);
        }

        for ($i = 0; $i < strlen($bytes); $i++) {
            $this->buffer[$offset++] = $bytes[$i];
        }

        return $this;
    }

    /**
     * Extracts a value from the buffer at the specified offset.
     *
     * @param FormatPackEnum|string $format
     * @param int $offset
     * @param int $length
     * @return mixed
     */
    protected function extract($format, $offset, $length)
    {
        $encoded = '';
        for ($i = 0; $i < $length; $i++) {
            $encoded .= $this->buffer->offsetGet($offset + $i);
        }

        if ($format == FormatPackEnum::N && PHP_INT_SIZE <= 4) {
            [, $h, $l] = unpack('n*', $encoded);
            $result = $l + $h * 0x010000;
        } elseif ($format == FormatPackEnum::V && PHP_INT_SIZE <= 4) {
            [, $h, $l] = unpack('v*', $encoded);
            $result = $h + $l * 0x010000;
        } else {
            [, $result] = unpack($format?->value ?? $format, $encoded);
        }

        return $result;
    }

    /**
     * Checks if the actual value exceeds the expected maximum size.
     *
     * @param mixed $excpectedMax
     * @param mixed $actual
     * @throws \InvalidArgumentException
     * @return static
     */
    protected function checkForOverSize($excpectedMax, $actual): self
    {
        if ($actual > $excpectedMax) {
            throw new \InvalidArgumentException(sprintf('%d exceeded limit of %d', $actual, $excpectedMax));
        }

        return $this;
    }

    public function length(): int
    {
        return $this->buffer->getSize();
    }

    public function getLastEmptyPosition(): int
    {
        foreach($this->buffer as $key => $value) {
            if (empty(trim($value))) {
                return $key;
            }
        }

        return 0;
    }

    /**
     * Writes a string to the buffer at the specified offset.
     *
     * @param string $value  The value that will be written.
     * @param int|null $offset The offset that the value will be written at.
     * @return static
     */
    public function write($value, $offset = null): self
    {
        if (null === $offset) {
            $offset = $this->getLastEmptyPosition();
        }

        $length = strlen($value);
        $this->insert('a' . $length, $value, $offset, $length);

        return $this;
    }

    /**
     * Writes an 8-bit signed integer to the buffer at the specified offset.
     *
     * @param int $value  The value that will be written.
     * @param int|null $offset The offset that the value will be written at.
     * @return static
     */
    public function writeInt8($value, $offset = null): self
    {
        if (null === $offset) {
            $offset = $this->getLastEmptyPosition();
        }

        $format = FormatPackEnum::C;
        $this->checkForOverSize(0xff, $value);
        $this->insert($format, $value, $offset, $format->getLength());

        return $this;
    }

    /**
     * Writes a 16-bit signed integer to the buffer at the specified offset.
     *
     * @param int $value  The value that will be written.
     * @param int|null $offset The offset that the value will be written at.
     * @return static
     */
    public function writeInt16BE($value, $offset = null): self
    {
        if (null === $offset) {
            $offset = $this->getLastEmptyPosition();
        }

        $format = FormatPackEnum::n;
        $this->checkForOverSize(0xffff, $value);
        $this->insert($format, $value, $offset, $format->getLength());

        return $this;
    }

    /**
     * Writes a 16-bit signed integer to the buffer at the specified offset.
     *
     * @param int $value  The value that will be written.
     * @param int|null $offset The offset that the value will be written at.
     * @return static
     */
    public function writeInt16LE($value, $offset = null): self
    {
        if (null === $offset) {
            $offset = $this->getLastEmptyPosition();
        }

        $format = FormatPackEnum::v;
        $this->checkForOverSize(0xffff, $value);
        $this->insert($format, $value, $offset, $format->getLength());

        return $this;
    }

    /**
     * Writes a 32-bit signed integer to the buffer at the specified offset.
     *
     * @param int $value  The value that will be written.
     * @param int|null $offset The offset that the value will be written at.
     * @return static
     */
    public function writeInt32BE($value, $offset = null): self
    {
        if (null === $offset) {
            $offset = $this->getLastEmptyPosition();
        }

        $format = FormatPackEnum::N;
        $this->checkForOverSize(0xffffffff, $value);
        $this->insert($format, $value, $offset, $format->getLength());

        return $this;
    }

    /**
     * Writes a 32-bit signed integer to the buffer at the specified offset.
     *
     * @param int $value  The value that will be written.
     * @param int|null $offset The offset that the value will be written at.
     * @return static
     */
    #[\Override]
    public function writeInt32LE($value, $offset = null): self
    {
        if (null === $offset) {
            $offset = $this->getLastEmptyPosition();
        }

        $format = FormatPackEnum::V;
        $this->checkForOverSize(0xffffffff, $value);
        $this->insert($format, $value, $offset, $format->getLength());

        return $this;
    }

    /**
     * Reads a string from the buffer at the specified offset.
     *
     * @param int $offset The offset to read from.
     * @param int $length The length of the string to read.
     * @return string The data read.
     */
    public function read($offset, $length)
    {
        return $this->extract('a' . $length, $offset, $length);
    }

    public function readInt8($offset)
    {
        $format = FormatPackEnum::C;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt16BE($offset)
    {
        $format = FormatPackEnum::n;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt16LE($offset)
    {
        $format = FormatPackEnum::v;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt32BE($offset)
    {
        $format = FormatPackEnum::N;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt32LE($offset)
    {
        $format = FormatPackEnum::V;
        return $this->extract($format, $offset, $format->getLength());
    }

    /**
     * Writes a 32-bit unsigned integer with big endian.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     */
    public function writeUInt32BE(int $value, int $offset): self
    {
        return $this->insert(FormatPackEnum::I, $value, $offset, 3);
    }

    /**
     * Writes a 64-bit unsigned integer with little endian.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     */
    public function writeUInt64LE(int $value, int $offset): self
    {
        return $this->insert(FormatPackEnum::P, $value, $offset, 8);
    }

    /**
     * Writes a signed integer.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     */
    public function writeInt(int $value, int $offset): self
    {
        return $this->insert(FormatPackEnum::N, $value, $offset, 4);
    }

    /**
     * Writes a unsigned integer.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     */
    public function writeUInt(int $value, int $offset): self
    {
        return $this->insert(FormatPackEnum::I, $value, $offset, 4);
    }

    /**
     * Reads a signed integer.
     *
     * @param int $offset The offset to read from.
     *
     * @return int The data read.
     */
    public function readInt(int $offset): int
    {
        return $this->extract(FormatPackEnum::N, $offset, 4);
    }

    /**
     * Reads a signed integer.
     *
     * @param int $offset The offset to read from.
     *
     * @return int The data read.
     */
    public function readUInt(int $offset): int
    {
        return $this->extract(FormatPackEnum::I, $offset, 4);
    }

    /**
     * Writes an unsigned big endian short.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     */
    public function writeShort(int $value, int $offset): self
    {
        return $this->insert(FormatPackEnum::n, $value, $offset, 2);
    }

    /**
     * Reads an unsigned big endian short.
     *
     * @param int $offset The offset to read from.
     *
     * @return int The data read.
     */
    public function readShort(int $offset): int
    {
        return $this->extract(FormatPackEnum::n, $offset, 4);
    }

    /**
     * Reads a unsigned integer with little endian.
     *
     * @param int $offset The offset that will be read.
     *
     * @return int The value that is at the specified offset.
     */
    public function readUIntLE(int $offset): int
    {
        return $this->extract(FormatPackEnum::I, $offset, 3);
    }

    public function readChar(int $offset): string
    {
        return $this->extract(FormatPackEnum::c, $offset, 1);
    }

    public function readUChar(int $offset): string
    {
        return $this->extract(FormatPackEnum::C, $offset, 1);
    }

    /**
     * Writes a char.
     *
     * @param string $value  The value that will be written.
     * @param int    $offset The offset that the value will be written.
     */
    public function writeChar(string $value, int $offset): self
    {
        return $this->insert(FormatPackEnum::c, $value, $offset, FormatPackEnum::c->getLength());
    }

    /**
     * Writes raw binary to the buffer.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written at.
     */
    public function writeRaw(int $value, int $offset): void
    {
        $this->buffer[$offset] = $value;
    }

    /**
     * Writes a binary string to the buffer.
     *
     * @param string $value  The value that will be written.
     * @param int    $offset The offset that the value will be written at.
     */
    public function writeRawString(string $value, int $offset): void
    {
        for ($i = 0; $i < strlen($value); ++$i) {
            $this->buffer[$offset++] = $value[$i];
        }
    }

    /**
     * Gets an attribute via key. Used for ArrayAccess.
     *
     * @param mixed $key The attribute key.
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->buffer[$key] ?? null;
    }

    /**
     * Checks if an attribute exists via key. Used for ArrayAccess.
     *
     * @param mixed $key The attribute key.
     *
     * @return bool Whether the offset exists.
     */
    public function offsetExists($key): bool
    {
        return isset($this->buffer[$key]);
    }

    /**
     * Sets an attribute via key. Used for ArrayAccess.
     *
     * @param mixed $key   The attribute key.
     * @param mixed $value The attribute value.
     */
    public function offsetSet($key, $value): void
    {
        $this->buffer[$key] = $value;
    }

    /**
     * Unsets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key The attribute key.
     */
    public function offsetUnset($key): void
    {
        if (isset($this->buffer[$key])) {
            unset($this->buffer[$key]);
        }
    }
}

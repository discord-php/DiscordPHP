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

namespace Discord\Helpers\ByteBuffer;

use Discord\Helpers\FormatPackEnum;

/**
 * Helper class for handling binary data.
 *
 * @author alexandre433
 *
 * @throws \InvalidArgumentException If invalid arguments are provided or buffer overflows.
 */
class Buffer extends AbstractBuffer implements \ArrayAccess
{
    use BufferArrayAccessTrait;

    protected \SplFixedArray $buffer;

    public function __construct($argument)
    {
        is_string($argument)
            ? $this->initializeStructs(strlen($argument), $argument)
            : (is_int($argument)
                ? $this->initializeStructs($argument, pack(FormatPackEnum::x->value . "$argument"))
                : throw new \InvalidArgumentException('Constructor argument must be an binary string or integer'));
    }

    public function __toString(): string
    {
        return implode('', iterator_to_array($this->buffer, false));
    }

    public static function make($argument): static
    {
        return new static($argument);
    }

    protected function initializeStructs($length, string $content): void
    {
        $this->buffer = new \SplFixedArray($length);
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
     * @param ?int $length
     * @return Buffer
     */
    protected function insert($format, $value, int $offset, ?int $length = null): self
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
    protected function extract(FormatPackEnum|string $format, int $offset, int $length)
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
    protected function checkForOverSize($expectedMax, string|int $actual): self
    {
        if ($actual > $expectedMax) {
            throw new \InvalidArgumentException("actual exceeded expectedMax limit");
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
    public function write($value, ?int $offset = null): self
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
    public function writeInt8($value, ?int $offset = null): self
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
    public function writeInt16BE($value, ?int $offset = null): self
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
    public function writeInt16LE($value, ?int $offset = null): self
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
    public function writeInt32BE($value, ?int $offset = null): self
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
    public function writeInt32LE($value, ?int $offset = null): self
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
    public function read(int $offset, int $length)
    {
        return $this->extract('a' . $length, $offset, $length);
    }

    public function readInt8(int $offset)
    {
        $format = FormatPackEnum::C;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt16BE(int $offset)
    {
        $format = FormatPackEnum::n;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt16LE(int $offset)
    {
        $format = FormatPackEnum::v;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt32BE(int $offset)
    {
        $format = FormatPackEnum::N;
        return $this->extract($format, $offset, $format->getLength());
    }

    public function readInt32LE(int $offset)
    {
        $format = FormatPackEnum::V;
        return $this->extract($format, $offset, $format->getLength());
    }
}

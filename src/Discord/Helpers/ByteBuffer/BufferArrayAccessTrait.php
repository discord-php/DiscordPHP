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
 * @author Valithor Obsidion <valithor@discordphp.org>
 */
trait BufferArrayAccessTrait
{
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
     * Gets an attribute via key. Used for \ArrayAccess.
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
     * Checks if an attribute exists via key. Used for \ArrayAccess.
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
     * Sets an attribute via key. Used for \ArrayAccess.
     *
     * @param mixed $key   The attribute key.
     * @param mixed $value The attribute value.
     */
    public function offsetSet($key, $value): void
    {
        $this->buffer[$key] = $value;
    }

    /**
     * Unsets an attribute via key. Used for \ArrayAccess.
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

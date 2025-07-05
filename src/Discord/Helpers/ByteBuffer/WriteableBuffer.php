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

/**
 * @author alexandre433
 */
interface WriteableBuffer
{
    public function write($value, ?int $offset = null): self;

    /**
     * Write an int8 to the buffer
     *
     * @param mixed $value
     * @param int|null $offset The offset to write the int8, if not provided the length of the buffer will be used
     * @return self
     */
    public function writeInt8($value, ?int $offset = null): self;

    /**
     * Write an int16 to the buffer in big-endian format
     *
     * @param mixed $value
     * @param int|null $offset The offset to write the int8, if not provided the length of the buffer will be used
     * @return self
     */
    public function writeInt16BE($value, ?int $offset = null): self;

    /**
     * Write an int16 to the buffer in little-endian format
     *
     * @param mixed $value
     * @param int|null $offset The offset to write the int8, if not provided the length of the buffer will be used
     * @return self
     */
    public function writeInt16LE($value, ?int $offset = null): self;

    /**
     * Write an int32 to the buffer in big-endian format
     *
     * @param mixed $value
     * @param int|null $offset The offset to write the int8, if not provided the length of the buffer will be used
     * @return self
     */
    public function writeInt32BE($value, ?int $offset = null): self;

    /**
     * Write an int32 to the buffer in little-endian format
     *
     * @param mixed $value
     * @param int|null $offset The offset to write the int8, if not provided the length of the buffer will be used
     * @return self
     */
    public function writeInt32LE($value, ?int $offset = null): self;

}

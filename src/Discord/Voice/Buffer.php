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

use TrafficCophp\ByteBuffer\Buffer as BaseBuffer;

/**
 * A Byte Buffer similar to Buffer in NodeJS.
 */
class Buffer extends BaseBuffer
{
    /**
     * Writes a 32-bit unsigned integer with big endian;.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     *
     * @return void
     */
    public function writeUInt32BE($value, $offset)
    {
        $this->checkForOverSize(0xffffffff, $value);
        $this->insert('I', $value, $offset, 3);
    }

    /**
     * Writes a signed integer.
     *
     * @param int $value  The value that will be written.
     * @param int $offset The offset that the value will be written.
     *
     * @return void
     */
    public function writeInt($value, $offset)
    {
        $this->insert('i', $value, $offset, 4);
    }

    /**
     * Writes a signed short.
     *
     * @param short $value  The value that will be written.
     * @param int   $offset The offset that the value will be written.
     *
     * @return void
     */
    public function writeShort($value, $offset)
    {
        $this->insert('s', $value, $offset, 2);
    }

    /**
     * Reads a unsigned integer with little endian.
     *
     * @param int $offset The offset that will be read.
     *
     * @return int The value that is at the specified offset.
     */
    public function readUIntLE($offset)
    {
        return $this->extract('I', $offset, 3);
    }

    /**
     * Writes a char.
     *
     * @param char $value  The value that will be written.
     * @param int  $offset The offset that the value will be written.
     *
     * @return void
     */
    public function writeChar($value, $offset)
    {
        $this->insert('c', $value, $offset, $this->lengthMap->getLengthFor('c'));
    }
}

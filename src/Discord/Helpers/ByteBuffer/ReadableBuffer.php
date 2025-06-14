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
interface ReadableBuffer
{
    public function read(int $offset, int $length);

    public function readInt8(int $offset);

    public function readInt16BE(int $offset);

    public function readInt16LE(int $offset);

    public function readInt32BE(int $offset);

    public function readInt32LE(int $offset);

}

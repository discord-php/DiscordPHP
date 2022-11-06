<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Exceptions;

use RuntimeException;

/**
 * Thrown when reading from a buffer times out.
 *
 * @since 10.0.0
 */
class BufferTimedOutException extends RuntimeException
{
    /**
     * Create a new buffer timeout exception.
     */
    public function __construct()
    {
        parent::__construct('Reading from the buffer timed out.');
    }
}

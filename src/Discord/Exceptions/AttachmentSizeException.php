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
 * Thrown when attachment size exceeds the maximum allowed size.
 *
 * @since 10.5.0
 */
class AttachmentSizeException extends RuntimeException
{
    /**
     * Create a new attachment size exception.
     */
    public function __construct()
    {
        parent::__construct('One or more attachments exceed the attachment size limit.');
    }
}

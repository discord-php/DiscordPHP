<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Exceptions\Runtime;

use RuntimeException;

/**
 * Thrown when attachment size exceeds the maximum allowed size.
 *
 * @since 10.10.0
 */
class RequiredExtensionNotLoadedException extends RuntimeException
{
    /**
     * Create a new required extension not loaded exception.
     */
    public function __construct()
    {
        parent::__construct('The ext-gmp extension is not loaded, it is required for 32-bits (x86) PHP.');
    }
}

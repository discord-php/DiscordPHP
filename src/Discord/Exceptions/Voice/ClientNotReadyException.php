<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Exceptions\Voice;

/**
 * Thrown when the Voice Client is not ready.
 *
 * @since 10.0.0
 */
final class ClientNotReadyException extends \RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'Voice Client is not ready.');
    }
}

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

/**
 * Thrown when FFmpeg is not compiled with libopus.
 *
 * @since 3.2.0
 */
class OpusNotFoundException extends \Exception
{
}

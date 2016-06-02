<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Exceptions\Rest;

use Discord\Exceptions\DiscordRequestFailedException;

/**
 * Thrown when the Discord servers return `content longer than 2000 characters` after
 * a REST request. The user must use WebSockets to obtain this data if they need it.
 */
class ContentTooLongException extends DiscordRequestFailedException
{
}

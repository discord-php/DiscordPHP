<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Exceptions;

/**
 * Thrown when the user tries to update the Discord user without providing
 * the account password.
 *
 * @see \Discord\Parts\User\Client
 */
class PasswordEmptyException extends \Exception
{
}

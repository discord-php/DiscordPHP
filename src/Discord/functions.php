<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Checks to see if a part has been mentioned.
 *
 * @param Part|string $part    The part or mention to look for.
 * @param Message     $message The message to check.
 *
 * @return bool Whether the part was mentioned.
 */
function mentioned($part, Message $message)
{
    if ($part instanceof User || $part instanceof Member) {
        return $message->mentions->has($part->id);
    } elseif ($part instanceof Role) {
        return $message->mention_roles->has($part->id);
    } elseif ($part instanceof Channel) {
        return strpos($message->content, "<#{$part->id}>") !== false;
    } else {
        return strpos($message->content, $part) !== false;
    }
}

/**
 * Calculates UNIX timestamp from snowflake.
 *
 * @param STRING $snowflake The snowflake
 *
 * @return UNIX timestamp
 */
function timestampFromSnowFlake($snowflake, $float = false)
{
    $timestamp = ((($snowflake >> 22) + 1420070400000) / 1000);

    return ($float) ? (float) $timestamp : (int) floor($timestamp);
}

/**
 * Calculates INT from RGB value.
 *
 * @param INT $r Red color
 * @param INT $g Green color
 * @param INT $b Blue color
 *
 * @return INT color
 */
function rgbToInteger($r = 0, $g = 0, $b = 0)
{
    return ($r << 16) + ($g << 8) + $b;
}

<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\ComponentEmbed;

use function Discord\poly_strlen;

/** @internal Primitive constraints for website preview authoring. */
final class InputValidator
{
    public static function url($url, int $max): void
    {
        if (! is_string($url) || poly_strlen($url) > $max || ! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Website preview URLs must be HTTP(S) and at most '.$max.' characters.');
        }
    }

    public static function text($text, int $max): void
    {
        if (! is_string($text) || poly_strlen($text) < 1 || poly_strlen($text) > $max) {
            throw new \InvalidArgumentException('Invalid website component text length.');
        }
    }

    public static function keys(array $object, array $allowed): void
    {
        if (array_diff(array_keys($object), $allowed)) {
            throw new \InvalidArgumentException('Unexpected field in website component embed.');
        }
    }

    public static function items($items, int $max): void
    {
        if (! is_array($items) || ! array_is_list($items) || count($items) < 1 || count($items) > $max) {
            throw new \InvalidArgumentException('Invalid component or media item list.');
        }
    }
}

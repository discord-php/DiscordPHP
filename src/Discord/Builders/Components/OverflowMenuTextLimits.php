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

namespace Discord\Builders\Components;

/**
 * Validates the Discord overflow menu text limits in UTF-8 characters.
 * @internal
 */
trait OverflowMenuTextLimits
{
    /**
     * @throws \InvalidArgumentException If the text is not valid UTF-8.
     * @throws \LengthException          If the text exceeds the field limit.
     */
    protected static function validateOverflowTextLength(string $value, int $limit, string $field): void
    {
        $length = preg_match_all('/./us', $value);
        if ($length === false) {
            throw new \InvalidArgumentException($field.' must be valid UTF-8.');
        }

        if ($length > $limit) {
            throw new \LengthException($field.' must be at most '.$limit.' characters.');
        }
    }
}

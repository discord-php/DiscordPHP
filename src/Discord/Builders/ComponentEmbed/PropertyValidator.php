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

/** @internal Optional component property types and limits. */
final class PropertyValidator
{
    public static function validate(array $component): void
    {
        self::integer($component, 'id', 4_294_967_295);
        self::integer($component, 'accent_color', 0xFF_FFFF);
        self::booleans($component);
        if (! in_array($component['spacing'] ?? 1, [1, 2], true)) {
            throw new \InvalidArgumentException('Separator spacing must be 1 or 2.');
        }
    }

    private static function integer(array $component, string $field, int $max): void
    {
        if (($component[$field] ?? null) === null) {
            return;
        }
        if (! is_int($component[$field]) || $component[$field] < 0 || $component[$field] > $max) {
            throw new \InvalidArgumentException($field.' must be an integer between 0 and '.$max.'.');
        }
    }

    private static function booleans(array $component): void
    {
        foreach (['spoiler', 'disabled', 'divider'] as $field) {
            if (array_key_exists($field, $component) && ! is_bool($component[$field])) {
                throw new \InvalidArgumentException($field.' must be a boolean.');
            }
        }
    }

    public static function emoji($emoji): void
    {
        if (! is_array($emoji)) {
            throw new \InvalidArgumentException('Button emoji must be an object.');
        }
        InputValidator::keys($emoji, ['id', 'name', 'animated']);
        if (($emoji['id'] ?? null) === null && ($emoji['name'] ?? '') === '') {
            throw new \InvalidArgumentException('Button emoji requires an id or name.');
        }
    }
}

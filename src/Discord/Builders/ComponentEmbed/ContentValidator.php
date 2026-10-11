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

/** @internal Display content and link button constraints. */
final class ContentValidator
{
    public static function validate(array $component): int
    {
        switch ($component['type']) {
            case 2:
                self::button($component);
                break;
            case 10:
                InputValidator::text($component['content'] ?? null, 4000);
                break;
            case 11:
                MediaValidator::item($component, 11);
                break;
            case 12:
                return MediaValidator::gallery($component['items'] ?? null);
        }

        return 0;
    }

    private static function button(array $component): void
    {
        if (($component['style'] ?? null) !== 5 || (($component['label'] ?? null) === null && ($component['emoji'] ?? null) === null)) {
            throw new \InvalidArgumentException('Website buttons require link style 5 and a label or emoji.');
        }
        InputValidator::url($component['url'] ?? null, 512);
        if (($component['label'] ?? null) !== null) {
            InputValidator::text($component['label'], 80);
        }
        if (($component['emoji'] ?? null) !== null) {
            PropertyValidator::emoji($component['emoji']);
        }
    }
}

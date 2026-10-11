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

/**
 * @internal Validates website preview tree shape and aggregate limits.
 * Stateless constraints deliberately use static calls.
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class ComponentValidator
{
    private const FIELDS = [
        1 => ['components'],
        2 => ['style', 'url', 'label', 'emoji', 'disabled'],
        9 => ['components', 'accessory'],
        10 => ['content'],
        11 => ['media', 'description', 'spoiler'],
        12 => ['items'],
        14 => ['divider', 'spacing'],
        17 => ['components', 'accent_color', 'spoiler'],
    ];

    public static function validate(array $component): void
    {
        $count = 0;
        $galleryItems = 0;
        self::visit($component, [17], $count, $galleryItems);
    }

    private static function visit(array $component, array $allowed, int &$count, int &$galleryItems): void
    {
        $type = $component['type'] ?? null;
        if (! in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported component type or placement in website preview.');
        }
        if (++$count > 40) {
            throw new \LengthException('Website component embeds allow at most 40 components including the root Container.');
        }
        InputValidator::keys($component, array_merge(['type', 'id'], self::FIELDS[$type]));
        PropertyValidator::validate($component);
        $galleryItems += ContentValidator::validate($component);
        if ($galleryItems > 10) {
            throw new \LengthException('Website previews allow at most 10 gallery items across all galleries.');
        }
        self::children($component, $count, $galleryItems);
        if ($type === 9) {
            if (! is_array($component['accessory'] ?? null)) {
                throw new \InvalidArgumentException('Section requires a Button or Thumbnail accessory.');
            }
            self::visit($component['accessory'], [2, 11], $count, $galleryItems);
        }
    }

    private static function children(array $component, int &$count, int &$galleryItems): void
    {
        $types = [1 => [2], 9 => [10], 17 => [1, 9, 10, 12, 14]];
        $limits = [1 => 5, 9 => 3, 17 => 39];
        $type = $component['type'];
        if (($types[$type] ?? null) === null) {
            return;
        }
        InputValidator::items($component['components'] ?? null, $limits[$type]);
        foreach ($component['components'] as $child) {
            if (! is_array($child)) {
                throw new \InvalidArgumentException('Components must be objects.');
            }
            self::visit($child, $types[$type], $count, $galleryItems);
        }
    }
}

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
 * @internal Media item shape and supported file suffixes; never fetches assets.
 * Stateless constraints deliberately use static calls.
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class MediaValidator
{
    public static function gallery($items): int
    {
        InputValidator::items($items, 10);
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new \InvalidArgumentException('Gallery items must be objects.');
            }
            InputValidator::keys($item, ['media', 'description', 'spoiler']);
            self::item($item, 12);
        }

        return count($items);
    }

    public static function item(array $item, int $type): void
    {
        $media = $item['media'] ?? null;
        if (! is_array($media)) {
            throw new \InvalidArgumentException('Media must be an object with a URL.');
        }
        InputValidator::keys($media, ['url']);
        InputValidator::url($media['url'] ?? null, 2048);
        self::format($media['url'], $type);
        if (($item['description'] ?? null) !== null) {
            InputValidator::text($item['description'], 1024);
        }
        if (array_key_exists('spoiler', $item) && ! is_bool($item['spoiler'])) {
            throw new \InvalidArgumentException('Media spoiler must be a boolean.');
        }
    }

    private static function format(string $url, int $type): void
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $formats = ['png', 'gif', 'jpg', 'jpeg', 'webp', 'avif'];
        if ($type === 12) {
            $formats = array_merge($formats, ['mp4', 'webm', 'mov']);
        }
        // A suffix is only a local guard, not proof of the fetched Content-Type.
        if ($extension !== '' && ! in_array($extension, $formats, true)) {
            throw new \InvalidArgumentException('Unsupported preview media extension; use a supported image/video or verify an extensionless URL separately.');
        }
    }
}

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

namespace Discord\Scripts;

/** Resolves mapped Part files while keeping mapping paths inside `src/`. */
final class OpenApiAutoCodeTargetLoader
{
    /**
     * @return array{relative: string, path: ?string, contents: ?string, skipped: list<string>}
     */
    public function load(string $schemaName, string $relative, string $sourceRoot): array
    {
        $path = realpath($sourceRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative));
        if (false === $path || ! str_starts_with($path, $sourceRoot.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            return ['relative' => $relative, 'path' => null, 'contents' => null, 'skipped' => ["{$schemaName}: mapped source file is missing or outside src/"]];
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            return ['relative' => $relative, 'path' => $path, 'contents' => null, 'skipped' => ["{$schemaName}: could not read {$relative}"]];
        }

        return ['relative' => $relative, 'path' => $path, 'contents' => $contents, 'skipped' => []];
    }
}

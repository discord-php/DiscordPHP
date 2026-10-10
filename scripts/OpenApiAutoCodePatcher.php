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

/** Applies the source-level additions produced by the schema mapper. */
final class OpenApiAutoCodePatcher
{
    /**
     * Patch each mapped file, reporting files that do not match the expected Part structure.
     *
     * @param array<string, array<string, string>> $byFile
     *
     * @return array{files: array<string, string>, added: list<string>, skipped: list<string>}
     */
    public static function patchFiles(string $sourceRoot, array $byFile): array
    {
        $files = [];
        $added = [];
        $skipped = [];
        foreach ($byFile as $relative => $fields) {
            $path = $sourceRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
            $contents = file_get_contents($path);
            if (false === $contents) {
                $skipped[] = "{$relative}: could not read source file";

                continue;
            }

            try {
                $files[$relative] = self::patchSource($contents, $fields);
            } catch (\RuntimeException $e) {
                foreach (array_keys($fields) as $field) {
                    $skipped[] = "{$relative}::{$field}: {$e->getMessage()}";
                }

                continue;
            }

            foreach ($fields as $field => $_) {
                $added[] = "{$relative}::\${$field}";
            }
        }

        return ['files' => $files, 'added' => $added, 'skipped' => $skipped];
    }

    /**
     * Add fields to the first property docblock and `$fillable` array in a Part source file.
     *
     * @param array<string, string> $fields
     */
    public static function patchSource(string $source, array $fields): string
    {
        $source = OpenApiAutoCodeDocblockPatcher::add($source, $fields);

        return OpenApiAutoCodeFillablePatcher::add($source, $fields);
    }
}

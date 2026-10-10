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

require_once __DIR__.'/OpenApiAutoCodeFieldMapper.php';
require_once __DIR__.'/OpenApiAutoCodeMapper.php';
require_once __DIR__.'/OpenApiAutoCodeProperty.php';
require_once __DIR__.'/OpenApiAutoCodeTargetLoader.php';
require_once __DIR__.'/OpenApiAutoCodeDocblockPatcher.php';
require_once __DIR__.'/OpenApiAutoCodeFillablePatcher.php';
require_once __DIR__.'/OpenApiAutoCodePatcher.php';

/**
 * Applies a deliberately narrow set of additive OpenAPI response-field changes to mapped Parts.
 *
 * Only a newly added, unformatted scalar property in a mapped stable response schema can be added to a
 * Part's docblock and `$fillable` list. Anything less certain is reported as skipped for a human to implement.
 */
final class OpenApiAutoCode
{
    /**
     * Compare the two decoded specs and return patched source files without writing them.
     *
     * @param array<string, mixed>               $before
     * @param array<string, mixed>               $after
     * @param array<string, array{file: string}> $mapping
     *
     * @return array{files: array<string, string>, added: list<string>, skipped: list<string>}
     */
    public static function generate(array $before, array $after, string $source, array $mapping): array
    {
        $beforeSchemas = $before['components']['schemas'] ?? null;
        $afterSchemas = $after['components']['schemas'] ?? null;
        if (! is_array($beforeSchemas) || ! is_array($afterSchemas)) {
            throw new \RuntimeException('Both specs must contain components.schemas objects.');
        }

        $sourceRoot = realpath($source);
        if (false === $sourceRoot) {
            throw new \RuntimeException("There is no source directory {$source}.");
        }

        $mapped = OpenApiAutoCodeMapper::map($beforeSchemas, $afterSchemas, $sourceRoot, $mapping);
        $patched = OpenApiAutoCodePatcher::patchFiles($sourceRoot, $mapped['files']);
        $skipped = [...$mapped['skipped'], ...$patched['skipped']];
        sort($patched['added']);
        sort($skipped);

        return ['files' => $patched['files'], 'added' => $patched['added'], 'skipped' => $skipped];
    }
}

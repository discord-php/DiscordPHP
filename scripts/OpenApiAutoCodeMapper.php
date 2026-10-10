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

/** Maps explicitly selected response schemas to source files. */
final class OpenApiAutoCodeMapper
{
    private OpenApiAutoCodeTargetLoader $targetLoader;

    private OpenApiAutoCodeFieldMapper $fieldMapper;

    public function __construct(?OpenApiAutoCodeTargetLoader $targetLoader = null, ?OpenApiAutoCodeFieldMapper $fieldMapper = null)
    {
        $this->targetLoader = $targetLoader ?? new OpenApiAutoCodeTargetLoader();
        $this->fieldMapper = $fieldMapper ?? new OpenApiAutoCodeFieldMapper();
    }

    /**
     * @param array<string, mixed>               $beforeSchemas
     * @param array<string, mixed>               $afterSchemas
     * @param array<string, array{file: string}> $mapping
     *
     * @return array{files: array<string, array<string, string>>, skipped: list<string>}
     */
    public function map(array $beforeSchemas, array $afterSchemas, string $sourceRoot, array $mapping): array
    {
        $byFile = [];
        $skipped = [];
        foreach ($mapping as $schemaName => $target) {
            $loaded = $this->targetLoader->load($schemaName, $target['file'], $sourceRoot);
            $skipped = [...$skipped, ...$loaded['skipped']];
            if (null === $loaded['contents']) {
                continue;
            }

            $old = $beforeSchemas[$schemaName] ?? null;
            $new = $afterSchemas[$schemaName] ?? null;
            $result = $this->fieldMapper->map($schemaName, $old, $new, $loaded['contents']);
            $skipped = [...$skipped, ...$result['skipped']];
            if ([] !== $result['fields']) {
                $relative = $loaded['relative'];
                $byFile[$relative] = [...$byFile[$relative] ?? [], ...$result['fields']];
            }
        }

        return ['files' => $byFile, 'skipped' => $skipped];
    }
}

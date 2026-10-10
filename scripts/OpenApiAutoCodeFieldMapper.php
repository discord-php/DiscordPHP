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

/** Maps safe additions from one OpenAPI schema to the fields a Part can expose. */
final class OpenApiAutoCodeFieldMapper
{
    /**
     * @param array<string, mixed>|null $oldSchema
     * @param array<string, mixed>|null $newSchema
     *
     * @return array{fields: array<string, string>, skipped: list<string>}
     */
    public static function map(string $schemaName, ?array $oldSchema, ?array $newSchema, string $source): array
    {
        $fields = [];
        $skipped = [];
        if (null === $newSchema || 'object' !== ($newSchema['type'] ?? null) || ! is_array($newSchema['properties'] ?? null)) {
            return ['fields' => [], 'skipped' => ["{$schemaName}: mapped schema is missing or is not an object"]];
        }

        $oldProperties = is_array($oldSchema['properties'] ?? null) ? $oldSchema['properties'] : [];
        $required = is_array($newSchema['required'] ?? null) ? $newSchema['required'] : [];
        $newProperties = $newSchema['properties'];
        foreach ($newProperties as $property => $definition) {
            if (array_key_exists($property, $oldProperties)) {
                continue;
            }

            $type = is_array($definition) ? OpenApiAutoCodeProperty::phpDocType($definition, $required, (string) $property) : null;
            if (null === $type || ! preg_match('/^[a-z][a-z0-9_]*$/', (string) $property)) {
                $skipped[] = "{$schemaName}.{$property}: not a supported scalar property";

                continue;
            }

            if (OpenApiAutoCodeProperty::existsIn($source, (string) $property)) {
                $skipped[] = "{$schemaName}.{$property}: already documented or fillable; left for a human to reconcile";

                continue;
            }

            $fields[(string) $property] = $type;
        }

        return ['fields' => $fields, 'skipped' => $skipped];
    }
}

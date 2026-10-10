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
    private OpenApiAutoCodeFieldPolicy $policy;

    public function __construct(?OpenApiAutoCodeFieldPolicy $policy = null)
    {
        $this->policy = $policy ?? new OpenApiAutoCodeFieldPolicy();
    }

    /**
     * @param array<string, mixed>|null $oldSchema
     * @param array<string, mixed>|null $newSchema
     *
     * @return array{fields: array<string, string>, skipped: list<string>}
     */
    public function map(string $schemaName, ?array $oldSchema, ?array $newSchema, string $source): array
    {
        $newProperties = $this->policy->properties($newSchema);
        if (null === $newProperties) {
            return ['fields' => [], 'skipped' => ["{$schemaName}: mapped schema is missing or is not an object"]];
        }

        $oldProperties = $this->policy->existingProperties($oldSchema);
        $required = $this->policy->required($newSchema);
        $fields = [];
        $skipped = [];
        foreach ($newProperties as $property => $definition) {
            if (array_key_exists($property, $oldProperties)) {
                continue;
            }

            [$field, $reason] = $this->policy->newField($schemaName, (string) $property, $definition, $required, $source);
            if (null === $field) {
                $skipped[] = $reason;

                continue;
            }

            $fields[$property] = $field;
        }

        return ['fields' => $fields, 'skipped' => $skipped];
    }
}

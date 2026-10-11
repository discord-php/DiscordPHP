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

/** Enforces the narrow property rules for generated Part fields. */
final class OpenApiAutoCodeFieldPolicy
{
    private OpenApiAutoCodeProperty $property;

    public function __construct(?OpenApiAutoCodeProperty $property = null)
    {
        $this->property = $property ?? new OpenApiAutoCodeProperty();
    }

    /**
     * @param array<string, mixed>|null $schema
     *
     * @return array<string, mixed>|null
     */
    public function properties(?array $schema): ?array
    {
        if (null === $schema || 'object' !== ($schema['type'] ?? null) || ! is_array($schema['properties'] ?? null)) {
            return null;
        }

        return $schema['properties'];
    }

    /**
     * @param array<string, mixed>|null $schema
     *
     * @return array<string, mixed>
     */
    public function existingProperties(?array $schema): array
    {
        $properties = $schema['properties'] ?? [];

        return is_array($properties) ? $properties : [];
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    public function required(array $schema): array
    {
        $required = $schema['required'] ?? [];

        return is_array($required) ? $required : [];
    }

    /**
     * @param mixed        $definition
     * @param list<string> $required
     *
     * @return array{?string, string}
     */
    public function newField(string $schemaName, string $property, mixed $definition, array $required, string $source): array
    {
        $type = is_array($definition) ? $this->property->phpDocType($definition, $required, $property) : null;
        if (null === $type || ! preg_match('/^[a-z][a-z0-9_]*$/', $property)) {
            return [null, "{$schemaName}.{$property}: not a supported scalar property"];
        }

        if ($this->property->existsIn($source, $property)) {
            return [null, "{$schemaName}.{$property}: already documented or fillable; left for a human to reconcile"];
        }

        return [$type, ''];
    }
}

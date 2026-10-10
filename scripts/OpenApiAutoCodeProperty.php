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

/** Converts supported API properties and checks for existing Part fields. */
final class OpenApiAutoCodeProperty
{
    /**
     * @param array<string, mixed> $definition
     * @param list<string>         $required
     */
    public function phpDocType(array $definition, array $required, string $property): ?string
    {
        if (array_intersect(['$ref', 'enum', 'oneOf', 'anyOf', 'allOf', 'items', 'format'], array_keys($definition)) !== []) {
            return null;
        }

        $types = $definition['type'] ?? null;
        $types = is_array($types) ? $types : [$types];
        $types = array_values(array_unique($types));
        $nullable = in_array('null', $types, true);
        $types = array_values(array_diff($types, ['null']));
        if (1 !== count($types) || ! in_array($types[0], ['string', 'integer', 'boolean'], true)) {
            return null;
        }

        $type = ['string' => 'string', 'integer' => 'int', 'boolean' => 'bool'][$types[0]];
        if ($nullable || ! in_array($property, $required, true)) {
            $type .= '|null';
        }

        return $type;
    }

    public function existsIn(string $source, string $property): bool
    {
        if (preg_match('/@property(?:-read)?\s+[^\s]+\s+\$'.preg_quote($property, '/').'(?:\s|$)/', $source)) {
            return true;
        }

        if (! preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\n[ \t]*\];/s', $source, $fillable)) {
            return true;
        }

        return (bool) preg_match('/[\'\"]'.preg_quote($property, '/').'[\'\"]/', $fillable[1]);
    }
}

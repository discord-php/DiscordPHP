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

/** Adds raw API properties to a Part's class docblock. */
final class OpenApiAutoCodeDocblockPatcher
{
    /**
     * @param array<string, string> $fields
     */
    public function add(string $source, array $fields): string
    {
        $newline = str_contains($source, "\r\n") ? "\r\n" : "\n";
        if (! preg_match('/\/\*\*[\s\S]*?@property[\s\S]*?\*\//', $source, $doc, PREG_OFFSET_CAPTURE)) {
            throw new \RuntimeException('the class has no property docblock');
        }

        $docText = $doc[0][0];
        if (! preg_match_all('/^[ \t]*\* @property(?:-read)?\s+(\S+)\s+\$[a-zA-Z_][a-zA-Z0-9_]*.*(?:\r?\n|$)/m', $docText, $propertyLines, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            throw new \RuntimeException('the class property docblock has no property lines');
        }

        $width = max(array_map(static fn (array $line): int => strlen($line[1][0]), $propertyLines));
        $lastPropertyLine = end($propertyLines)[0];
        $insertAt = $doc[0][1] + $lastPropertyLine[1] + strlen($lastPropertyLine[0]);
        $additions = '';
        foreach ($fields as $field => $type) {
            $additions .= ' * @property '.str_pad($type, $width).' $'.$field.' Added from Discord\'s stable OpenAPI response schema.'.$newline;
        }

        return substr($source, 0, $insertAt).$additions.substr($source, $insertAt);
    }
}

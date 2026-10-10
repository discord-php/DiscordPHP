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

/** Adds raw API properties to a Part's `$fillable` array. */
final class OpenApiAutoCodeFillablePatcher
{
    /**
     * @param array<string, string> $fields
     */
    public function add(string $source, array $fields): string
    {
        $newline = str_contains($source, "\r\n") ? "\r\n" : "\n";
        if (! preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\n([ \t]*)\];/s', $source, $fillable, PREG_OFFSET_CAPTURE)) {
            throw new \RuntimeException('the class has no simple `$fillable` array');
        }

        $body = $fillable[1][0];
        preg_match('/\n([ \t]+)[\'\"][a-z][a-z0-9_]*[\'\"]/i', $body, $indentMatch);
        $indent = $indentMatch[1] ?? '        ';
        $insertAt = strlen($body);
        $prefix = $newline;
        if (preg_match('/^([ \t]*\/\/ @internal)/m', $body, $internal, PREG_OFFSET_CAPTURE)) {
            $insertAt = $internal[0][1];
            $prefix = '';
        }

        $additions = $prefix;
        foreach (array_keys($fields) as $field) {
            $additions .= $indent.'\''.$field.'\','.$newline;
        }

        $body = substr($body, 0, $insertAt).$additions.substr($body, $insertAt);
        $fillableText = str_replace($fillable[1][0], $body, $fillable[0][0]);

        return substr($source, 0, $fillable[0][1]).$fillableText.substr($source, $fillable[0][1] + strlen($fillable[0][0]));
    }
}

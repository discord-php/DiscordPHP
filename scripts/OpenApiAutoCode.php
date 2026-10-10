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

/**
 * Applies a deliberately narrow set of additive OpenAPI response-field changes to mapped Parts.
 *
 * This does not generate request behavior, nested Parts, enums, or accessors. Only a newly added,
 * unformatted scalar property in a mapped stable response schema can be added to a Part's docblock and
 * `$fillable` list. Anything less certain is reported as skipped for a human to implement.
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
        $beforeSchemas = $before['components']['schemas'] ?? [];
        $afterSchemas = $after['components']['schemas'] ?? [];
        if (! is_array($beforeSchemas) || ! is_array($afterSchemas)) {
            throw new \RuntimeException('Both specs must contain components.schemas objects.');
        }

        $sourceRoot = realpath($source);
        if (false === $sourceRoot) {
            throw new \RuntimeException("There is no source directory {$source}.");
        }

        $byFile = [];
        $skipped = [];
        foreach ($mapping as $schemaName => $target) {
            $old = $beforeSchemas[$schemaName] ?? [];
            $new = $afterSchemas[$schemaName] ?? null;
            if (! is_array($new) || 'object' !== ($new['type'] ?? null) || ! is_array($new['properties'] ?? null)) {
                $skipped[] = "{$schemaName}: mapped schema is missing or is not an object";

                continue;
            }

            $oldProperties = is_array($old) && is_array($old['properties'] ?? null) ? $old['properties'] : [];
            $required = is_array($new['required'] ?? null) ? $new['required'] : [];
            $relative = $target['file'];
            $path = realpath($sourceRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative));
            if (false === $path || ! str_starts_with($path, $sourceRoot.DIRECTORY_SEPARATOR) || ! is_file($path)) {
                $skipped[] = "{$schemaName}: mapped source file is missing or outside src/";

                continue;
            }

            $contents = file_get_contents($path);
            if (false === $contents) {
                $skipped[] = "{$schemaName}: could not read {$relative}";

                continue;
            }

            foreach ($new['properties'] as $property => $definition) {
                if (array_key_exists($property, $oldProperties)) {
                    continue;
                }

                $type = is_array($definition) ? OpenApiAutoCodePatcher::phpDocType($definition, $required, (string) $property) : null;
                if (null === $type || ! preg_match('/^[a-z][a-z0-9_]*$/', (string) $property)) {
                    $skipped[] = "{$schemaName}.{$property}: not a supported scalar property";

                    continue;
                }

                if (OpenApiAutoCodePatcher::containsProperty($contents, (string) $property)) {
                    $skipped[] = "{$schemaName}.{$property}: already documented or fillable; left for a human to reconcile";

                    continue;
                }

                $byFile[$relative][(string) $property] = $type;
            }
        }

        $files = [];
        $added = [];
        foreach ($byFile as $relative => $fields) {
            $path = $sourceRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
            $contents = file_get_contents($path);
            if (false === $contents) {
                $skipped[] = "{$relative}: could not read source file";

                continue;
            }

            try {
                $files[$relative] = OpenApiAutoCodePatcher::patchSource($contents, $fields);
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

        sort($added);
        sort($skipped);

        return ['files' => $files, 'added' => $added, 'skipped' => $skipped];
    }
}

/** @internal Keeps source parsing and PHPDoc patching separate from spec comparison. */
final class OpenApiAutoCodePatcher
{
    /**
     * Add fields to the first property docblock and `$fillable` array in a Part source file.
     *
     * @param array<string, string> $fields
     */
    public static function patchSource(string $source, array $fields): string
    {
        if ([] === $fields) {
            return $source;
        }

        $newline = str_contains($source, "\r\n") ? "\r\n" : "\n";
        if (! preg_match('/\/\*\*[\s\S]*?@property[\s\S]*?\*\//', $source, $doc, PREG_OFFSET_CAPTURE)) {
            throw new \RuntimeException('the class has no property docblock');
        }

        $docText = $doc[0][0];
        if (! preg_match_all('/^[ \t]*\* @property(?:-read)?\s+(\S+)\s+\$[a-zA-Z_][a-zA-Z0-9_]*.*(?:\r?\n|$)/m', $docText, $propertyLines, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            throw new \RuntimeException('the class property docblock has no property lines');
        }

        $maxTypeWidth = 0;
        foreach ($propertyLines as $line) {
            $maxTypeWidth = max($maxTypeWidth, strlen($line[1][0]));
        }
        $lastPropertyLine = end($propertyLines)[0];
        $docInsertion = $doc[0][1] + $lastPropertyLine[1] + strlen($lastPropertyLine[0]);
        $docAdditions = '';
        foreach ($fields as $field => $type) {
            $docAdditions .= ' * @property '.str_pad($type, $maxTypeWidth).' $'.$field.' Added from Discord\'s stable OpenAPI response schema.'.$newline;
        }
        $source = substr($source, 0, $docInsertion).$docAdditions.substr($source, $docInsertion);

        if (! preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\n([ \t]*)\];/s', $source, $fillable, PREG_OFFSET_CAPTURE)) {
            throw new \RuntimeException('the class has no simple `$fillable` array');
        }

        $fillableBody = $fillable[1][0];
        preg_match('/\n([ \t]+)[\'\"][a-z][a-z0-9_]*[\'\"]/i', $fillableBody, $indentMatch);
        $indent = $indentMatch[1] ?? '        ';
        $insertAt = strlen($fillableBody);
        $prefix = $newline;
        if (preg_match('/^([ \t]*\/\/ @internal)/m', $fillableBody, $internal, PREG_OFFSET_CAPTURE)) {
            $insertAt = $internal[0][1];
            $prefix = '';
        }

        $fillableAdditions = $prefix;
        foreach ($fields as $field => $_) {
            $fillableAdditions .= $indent.'\''.$field.'\','.$newline;
        }

        $fillableBody = substr($fillableBody, 0, $insertAt).$fillableAdditions.substr($fillableBody, $insertAt);
        $fillableText = $fillable[0][0];
        $fillableText = str_replace($fillable[1][0], $fillableBody, $fillableText);

        return substr($source, 0, $fillable[0][1]).$fillableText.substr($source, $fillable[0][1] + strlen($fillable[0][0]));
    }

    /**
     * @param array<string, mixed> $definition
     * @param list<string>         $required
     */
    public static function phpDocType(array $definition, array $required, string $property): ?string
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

    public static function containsProperty(string $source, string $property): bool
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

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

// Apply only simple additive fields from explicitly mapped stable OpenAPI response schemas.
//
//   php scripts/openapi-autocode.php --before=OLD_STABLE.json --after=NEW_STABLE.json

require __DIR__.'/OpenApiAutoCode.php';

use Discord\Scripts\OpenApiAutoCode;

function read_spec(string $path): array
{
    if (! is_file($path)) {
        throw new RuntimeException("There is no file {$path}.");
    }

    $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($decoded)) {
        throw new RuntimeException("The spec {$path} must contain a JSON object.");
    }

    return $decoded;
}

$arguments = [];
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--before=')) {
        $arguments['before'] = substr($argument, 9);
    } elseif (str_starts_with($argument, '--after=')) {
        $arguments['after'] = substr($argument, 8);
    } else {
        fwrite(STDERR, "Usage: php scripts/openapi-autocode.php --before=OLD_STABLE.json --after=NEW_STABLE.json\n");

        exit(2);
    }
}

if (! isset($arguments['before'], $arguments['after'])) {
    fwrite(STDERR, "Usage: php scripts/openapi-autocode.php --before=OLD_STABLE.json --after=NEW_STABLE.json\n");

    exit(2);
}

try {
    $mappingPath = __DIR__.'/openapi-autocode-map.json';
    $mapping = json_decode((string) file_get_contents($mappingPath), true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($mapping) || ! is_array($mapping['schemas'] ?? null)) {
        throw new RuntimeException("The mapping {$mappingPath} has no schemas object.");
    }

    $result = OpenApiAutoCode::generate(
        read_spec($arguments['before']),
        read_spec($arguments['after']),
        dirname(__DIR__).DIRECTORY_SEPARATOR.'src',
        $mapping['schemas'],
    );

    foreach ($result['files'] as $relative => $contents) {
        $path = dirname(__DIR__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (false === file_put_contents($path, $contents)) {
            throw new RuntimeException("Could not write {$path}.");
        }
    }

    echo count($result['added'])." supported OpenAPI field(s) added.\n";
    foreach ($result['added'] as $field) {
        echo "  + {$field}\n";
    }

    if ([] !== $result['skipped']) {
        echo "\n".count($result['skipped'])." field(s) were skipped for human review:\n";
        foreach ($result['skipped'] as $field) {
            echo "  - {$field}\n";
        }
    }
} catch (JsonException|RuntimeException $e) {
    fwrite(STDERR, $e->getMessage()."\n");

    exit(1);
}

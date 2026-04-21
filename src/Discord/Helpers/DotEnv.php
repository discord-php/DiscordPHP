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

namespace Discord\Helpers;

/**
 * Minimal .env file loader with no external dependencies.
 *
 * Supports `# comment` lines, surrounding single/double quotes, and never
 * overrides variables that are already present in the environment.
 *
 * @since 10.1.0
 */
class DotEnv
{
    /**
     * Load a .env file and populate the environment.
     *
     * Returns the number of variables that were set, or `null` when the file
     * does not exist (not an error — `.env` is always optional at runtime).
     *
     * @param string $path Absolute or relative path to the .env file.
     *
     * @return int|null Number of variables set, or null if the file was not found.
     */
    public static function load(string $path): ?int
    {
        if (! file_exists($path)) {
            return null;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding matching quotes
            if (
                strlen($value) > 1
                && (
                    ($value[0] === '"' && $value[-1] === '"')
                    || ($value[0] === "'" && $value[-1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            $count++;
        }

        return $count;
    }

    /**
     * Load the .env file from the current working directory.
     *
     * Equivalent to `DotEnv::load(getcwd() . '/.env')`.
     *
     * @return int|null Number of variables set, or null if the file was not found.
     */
    public static function loadDefault(): ?int
    {
        return static::load(getcwd().'/.env');
    }
}

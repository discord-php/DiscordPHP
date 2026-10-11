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

// Checks that Discord::VERSION, which bots log at startup and quote in bug reports, keeps up with the
// release tags.
//
//   php scripts/check-version.php          VERSION must not be older than the newest v* tag.
//   php scripts/check-version.php v10.67.0 VERSION must be exactly this tag (run when it is released).
//
// Exits 0 when it holds, 1 when it does not, and 2 when it cannot be checked.
// It needs the repository's tags, so a CI checkout
// fetches them (fetch-depth: 0).

const FILE = __DIR__.'/../src/Discord/Discord.php';

if (1 !== preg_match("/public const VERSION = '(v[^']+)';/", (string) file_get_contents(FILE), $match)) {
    fwrite(STDERR, 'Could not find VERSION in '.FILE.PHP_EOL);
    exit(2);
}
$version = $match[1];

if (isset($argv[1])) {
    if ($version !== $argv[1]) {
        echo "Discord::VERSION is {$version}, but this release is {$argv[1]}. Set VERSION to {$argv[1]}.".PHP_EOL;
        exit(1);
    }
    echo "Discord::VERSION matches the release, {$version}.".PHP_EOL;
    exit(0);
}

// Use this checkout even when called from elsewhere. Double quotes also work in Windows' shell.
exec('git -C '.escapeshellarg(dirname(__DIR__)).' tag --list "v*"', $tags, $status);
if (0 !== $status) {
    fwrite(STDERR, 'Could not list the git tags.'.PHP_EOL);
    exit(2);
}
// Pre-release tags (v8.0.0-RC.1) never count as the newest release.
$tags = array_filter($tags, fn (string $tag) => 1 === preg_match('/^v\d+\.\d+\.\d+$/', $tag));
if ([] === $tags) {
    fwrite(STDERR, 'No stable release tags to compare Discord::VERSION with. Fetch the tags before running this check.'.PHP_EOL);
    exit(2);
}
usort($tags, 'version_compare');
$latest = end($tags);

if (version_compare($version, $latest, '<')) {
    echo "Discord::VERSION is {$version}, older than the latest release, {$latest}. Set it to {$latest} or the version about to be released.".PHP_EOL;
    exit(1);
}
echo "Discord::VERSION, {$version}, is not older than the latest release, {$latest}.".PHP_EOL;

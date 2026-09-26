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

// Checks DiscordPHP against the live preview of Discord's OpenAPI description: the operations it sends no
// request for, and what Discord has changed since the commit recorded in scripts/openapi-baseline.json.
//
//   composer openapi                 Report, and fail on anything new since the baseline.
//   composer openapi -- --all        Also list the gaps the baseline already knows about.
//   composer openapi -- --spec=FILE  Check a local copy of the spec instead of the live one.
//   composer openapi:update          Record the live spec's commit and today's gaps as the baseline.
//
// Downloaded editions of the spec are kept in the system's temporary directory, one file per commit.

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/OpenApiCheck.php';

exit(Discord\Scripts\OpenApiCheck::run(
    array_slice($argv, 1),
    __DIR__.DIRECTORY_SEPARATOR.'openapi-baseline.json',
    dirname(__DIR__).DIRECTORY_SEPARATOR.'src',
    sys_get_temp_dir().DIRECTORY_SEPARATOR.'discordphp-openapi',
));

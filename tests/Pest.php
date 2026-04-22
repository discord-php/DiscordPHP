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

// DiscordTestCase is not autoloaded; require it explicitly so that it is
// available when Pest.php is parsed (before phpunit.xml bootstrap runs).
require_once __DIR__.'/DiscordTestCase.php';

// Bind DiscordTestCase to integration test files so that $this->channel(),
// setUpBeforeClass(), and other class-level helpers are available inside
// Pest closures without extending the class explicitly.
uses(DiscordTestCase::class)->in(
    'Parts/Channel/ChannelTest.php',
    'Parts/Channel/Message',
    'Parts/Embed',
);

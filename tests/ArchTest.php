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

// ──────────────────────────────────────────────────────────────────────────────
// Source architecture
// ──────────────────────────────────────────────────────────────────────────────

arch('all source files declare strict types')
    ->expect('Discord\Builders')
    ->toUseStrictTypes();

// Builders are pure payload objects: they must NOT call the Discord HTTP layer
// or touch the event loop directly. Anything in Discord\Builders that fails
// this rule can be unit tested — add a test rather than marking it integration-only.
arch('builders are pure — no direct HTTP or event-loop calls')
    ->expect('Discord\Builders')
    ->not->toUse([
        'Discord\Http\Http',
        'React\EventLoop\LoopInterface',
        'React\EventLoop\Loop',
    ]);

// ──────────────────────────────────────────────────────────────────────────────
// Test suite hygiene
// ──────────────────────────────────────────────────────────────────────────────

// Prevent class-based PHPUnit tests from sneaking back in.
// If a new test class extends TestCase directly it should use Pest it()/test() instead.
arch('test files do not extend PHPUnit TestCase directly')
    ->expect('Tests')
    ->not->toExtend('PHPUnit\Framework\TestCase');

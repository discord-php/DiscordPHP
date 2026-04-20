<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Role;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use React\Promise\Deferred;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\contains;
use function Discord\deferFind;
use function Discord\escapeMarkdown;
use function Discord\getColor;
use function Discord\getSnowflakeTimestamp;
use function Discord\imageToBase64;
use function Discord\mentioned;
use function Discord\normalizePartId;
use function Discord\nowait;
use function Discord\poly_strlen;
use function Discord\studly;
use function React\Promise\resolve;

// ── contains() ───────────────────────────────────────────────────────────────

it('contains matches substrings', function (bool $expected, string $haystack, array $needles) {
    expect(contains($haystack, $needles))->toBe($expected);
})->with([
    [true, 'hello, world!', ['hello']],
    [true, 'phpunit tests', ['p', 'u']],
    [false, 'phpunit tests', ['a']],
]);

it('contains returns false for empty needles array', function () {
    expect(contains('hello', []))->toBeFalse();
});

it('contains returns true for an empty string needle', function () {
    // str_contains($str, '') is always true in PHP
    expect(contains('anything', ['']))->toBeTrue();
    expect(contains('', ['']))->toBeTrue();
});

// ── getColor() ────────────────────────────────────────────────────────────────

it('getColor resolves named and numeric colors', function (int $expected, string|int $color) {
    expect(getColor($color))->toBe($expected);
})->with([
    [0xcd5c5c, 'indianred'],
    [0x00bfff, 'deepskyblue'],
    [0x00bfff, 0x00bfff],
    [0, 0],
    [0x00bfff, '0x00bfff'],
]);

it('getColor resolves # prefixed hex strings', function () {
    expect(getColor('#00bfff'))->toBe(0x00bfff);
    expect(getColor('#ffffff'))->toBe(0xffffff);
    expect(getColor('#000000'))->toBe(0);
});

it('getColor resolves bare hex strings without prefix', function () {
    // Bare hex strings with at least one digit bypass the color-name check
    expect(getColor('00bfff'))->toBe(0x00bfff);
    expect(getColor('1a2b3c'))->toBe(0x1a2b3c);
    // All-alpha bare strings (e.g. 'ffffff') hit the color-name path and return 0
    expect(getColor('ffffff'))->toBe(0);
});

it('getColor returns 0 for unknown color names', function () {
    expect(getColor('notacolor'))->toBe(0);
    expect(getColor('discordblurple'))->toBe(0);
});

// ── poly_strlen() ─────────────────────────────────────────────────────────────

it('poly_strlen returns character count', function (int $expected, string $str) {
    expect(poly_strlen($str))->toBe($expected);
})->with([
    [5, 'abcde'],
    [0, ''],
    [1, ' '],
]);

it('poly_strlen counts multi-byte UTF-8 characters correctly', function () {
    // 'héllo' is 5 characters but 6 bytes in UTF-8
    expect(poly_strlen('héllo'))->toBe(5);
    expect(poly_strlen('日本語'))->toBe(3);
});

it('poly_strlen respects explicit encoding', function () {
    expect(poly_strlen('héllo', 'UTF-8'))->toBe(5);
});

// ── studly() ──────────────────────────────────────────────────────────────────

it('studly converts strings to StudlyCase', function (string $input, string $expected) {
    expect(studly($input))->toBe($expected);
})->with([
    ['trains are cool', 'TrainsAreCool'],
    ['robo smells like bananas', 'RoboSmellsLikeBananas'],
    ['i LiKE TuRtLEs', 'ILikeTurtles'],
]);

it('studly handles strings with numbers and special delimiters', function () {
    expect(studly('foo_bar-baz'))->toBe('FooBarBaz');
    expect(studly('hello123world'))->toBe('Hello123world');
    expect(studly('snake_case_string'))->toBe('SnakeCaseString');
});

// ── mentioned() ───────────────────────────────────────────────────────────────

it('mentioned detects user and member mentions', function () {
    $mockDiscord = getMockDiscord();

    $cases = [
        'member in mentions' => [
            new Member($mockDiscord, ['id' => '12345']),
            ['mentions' => [(object) ['id' => '12345']]],
            true,
        ],
        'member not in mentions' => [
            new Member($mockDiscord, ['id' => '123456']),
            ['mentions' => [(object) ['id' => '12345']]],
            false,
        ],
        'user in mentions' => [
            new User($mockDiscord, ['id' => '12345']),
            ['mentions' => [(object) ['id' => '12345']]],
            true,
        ],
        'user not in mentions' => [
            new User($mockDiscord, ['id' => '123456']),
            ['mentions' => [(object) ['id' => '12345']]],
            false,
        ],
        'user in mentions with several more' => [
            new User($mockDiscord, ['id' => '12345']),
            ['mentions' => [(object) ['id' => '123456'], (object) ['id' => '1234567'], (object) ['id' => '12345']]],
            true,
        ],
        'user not in mentions with several more' => [
            new User($mockDiscord, ['id' => '1234']),
            ['mentions' => [(object) ['id' => '123456'], (object) ['id' => '1234567'], (object) ['id' => '12345']]],
            false,
        ],
    ];

    foreach ($cases as [$part, $data, $expected]) {
        expect(mentioned($part, new Message($mockDiscord, $data)))->toBe($expected);
    }
});

it('mentioned detects role mentions via mention_roles', function () {
    $mockDiscord = getMockDiscord();

    $role = new Role($mockDiscord, ['id' => '999']);
    // mention_roles raw data is an array of string role IDs
    $hit = new Message($mockDiscord, ['mention_roles' => ['999']]);
    $miss = new Message($mockDiscord, ['mention_roles' => ['888']]);

    expect(mentioned($role, $hit))->toBeTrue();
    expect(mentioned($role, $miss))->toBeFalse();
});

it('mentioned detects channel mentions in message content', function () {
    $mockDiscord = getMockDiscord();

    $channel = new Channel($mockDiscord, ['id' => '888']);
    $hit = new Message($mockDiscord, ['content' => 'Check out <#888> for news']);
    $miss = new Message($mockDiscord, ['content' => 'No channel here']);

    expect(mentioned($channel, $hit))->toBeTrue();
    expect(mentioned($channel, $miss))->toBeFalse();
});

it('mentioned detects thread mentions in message content', function () {
    $mockDiscord = getMockDiscord();

    $thread = new Thread($mockDiscord, ['id' => '777']);
    $hit = new Message($mockDiscord, ['content' => 'See <#777> thread']);
    $miss = new Message($mockDiscord, ['content' => 'Nothing here']);

    expect(mentioned($thread, $hit))->toBeTrue();
    expect(mentioned($thread, $miss))->toBeFalse();
});

it('mentioned returns false for unrecognised part types', function () {
    $mockDiscord = getMockDiscord();
    $role = new Role($mockDiscord, ['id' => '111']);

    // Pass a Role as if it were a string (hits the default branch)
    expect(mentioned('some random string', new Message($mockDiscord, [])))->toBeFalse();
});

// ── normalizePartId() ─────────────────────────────────────────────────────────

it('normalizePartId extracts IDs from Parts and strings', function () {
    $mockDiscord = getMockDiscord();
    $normalize = normalizePartId();
    $resolver = new OptionsResolver();

    $cases = [
        [new User($mockDiscord, ['id' => '12345']), '12345'],
        [new Channel($mockDiscord, ['id' => '12345']), '12345'],
        [new Role($mockDiscord, ['id' => '12345']), '12345'],
        ['12345', '12345'],
        [null, null],
    ];

    foreach ($cases as [$input, $expected]) {
        expect($normalize($resolver, $input))->toBe($expected);
    }
});

it('normalizePartId uses a custom id_field', function () {
    $mockDiscord = getMockDiscord();
    $normalize = normalizePartId('guild_id');
    $resolver = new OptionsResolver();

    $member = new Member($mockDiscord, ['guild_id' => '9999']);
    expect($normalize($resolver, $member))->toBe('9999');
});

// ── escapeMarkdown() ──────────────────────────────────────────────────────────

it('escapeMarkdown escapes markdown characters', function (string $input, string $expected) {
    expect(escapeMarkdown($input))->toBe($expected);
})->with([
    [
        'hello there this is plain text, nothing should be escaped in here! :D, except the colon',
        'hello there this is plain text, nothing should be escaped in here! \:D, except the colon',
    ],
    [
        'I ~~really~~ like ||trains||',
        'I \~\~really\~\~ like \|\|trains\|\|',
    ],
    [
        '**Bananas**, in @@pyjamas',
        '\*\*Bananas\*\*, in \@\@pyjamas',
    ],
    [
        '>Lopen naar de #zee',
        '\>Lopen naar de \#zee',
    ],
    [
        'actually nothing should be changed now',
        'actually nothing should be changed now',
    ],
]);

it('escapeMarkdown escapes all formatting symbols', function () {
    expect(escapeMarkdown('#*:>@_`|~'))->toBe('\#\*\:\>\@\_\`\|\~');
});

it('escapeMarkdown returns empty string unchanged', function () {
    expect(escapeMarkdown(''))->toBe('');
});

// ── imageToBase64() ───────────────────────────────────────────────────────────

it('imageToBase64 throws for a non-existent file', function () {
    imageToBase64('/nonexistent/path/that/does/not/exist.png');
})->throws(\InvalidArgumentException::class, 'does not exist');

it('imageToBase64 throws for an unsupported file type', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'dphp_test_');
    file_put_contents($tmp, 'this is plain text, not an image');
    try {
        imageToBase64($tmp);
    } finally {
        @unlink($tmp);
    }
})->throws(\InvalidArgumentException::class, 'not one of jpeg');

it('imageToBase64 returns a base64 data URI for a valid PNG', function () {
    $tmp = sys_get_temp_dir() . '/dphp_test_' . uniqid() . '.png';

    // Write a minimal valid 1×1 PNG (no GD dependency)
    file_put_contents($tmp, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIA' .
        'BQAABjkB6QAAAABJRU5ErkJggg=='
    ));

    try {
        $result = imageToBase64($tmp);
    } finally {
        @unlink($tmp);
    }

    expect($result)->toContain('data:image/png;base64,');
});

// ── getSnowflakeTimestamp() ───────────────────────────────────────────────────

it('getSnowflakeTimestamp returns a float after the Discord epoch for a valid snowflake', function (string $snowflake) {
    $ts = getSnowflakeTimestamp($snowflake);
    expect($ts)
        ->toBeFloat()
        ->toBeGreaterThan(1420070400.0); // After January 1, 2015 (Discord epoch)
})->with([
    ['175928847299117063'], // approximate Discord epoch + ~42 seconds
    ['1015047895165677588'], // a later snowflake (~2022)
]);

it('getSnowflakeTimestamp returns consistent values for the same input', function () {
    $ts1 = getSnowflakeTimestamp('175928847299117063');
    $ts2 = getSnowflakeTimestamp('175928847299117063');

    expect($ts1)->toBe($ts2);
});

it('getSnowflakeTimestamp increases monotonically with larger snowflakes', function () {
    $older = getSnowflakeTimestamp('175928847299117063');
    $newer = getSnowflakeTimestamp('1015047895165677588');

    expect($newer)->toBeGreaterThan($older);
});

// ── nowait() ──────────────────────────────────────────────────────────────────

it('nowait returns the resolved value from an already-resolved promise', function () {
    expect(nowait(resolve('hello')))->toBe('hello');
    expect(nowait(resolve(42)))->toBe(42);
    expect(nowait(resolve(true)))->toBeTrue();
});

it('nowait returns null for a deferred promise that has not resolved', function () {
    $deferred = new Deferred();
    expect(nowait($deferred->promise()))->toBeNull();
});

// ── deferFind() ───────────────────────────────────────────────────────────────

it('deferFind resolves with the first matching element', function () {
    $result = wait(function ($discord, $resolve) {
        deferFind([1, 2, 3, 4, 5], fn ($x) => $x === 3, $discord->getLoop())
            ->then($resolve);
    });

    expect($result)->toBe(3);
});

it('deferFind resolves with null when no element matches', function () {
    $result = wait(function ($discord, $resolve) {
        deferFind([1, 2, 4, 5], fn ($x) => $x === 3, $discord->getLoop())
            ->then($resolve);
    });

    expect($result)->toBeNull();
});

it('deferFind resolves with null for an empty array', function () {
    $result = wait(function ($discord, $resolve) {
        deferFind([], fn ($x) => true, $discord->getLoop())
            ->then($resolve);
    });

    expect($result)->toBeNull();
});

it('deferFind rejects with RuntimeException when cancelled', function () {
    $result = wait(function ($discord, $resolve) {
        $promise = deferFind(
            array_fill(0, 10000, 1),
            fn ($x) => false,
            $discord->getLoop()
        );

        // Cancel immediately after starting
        $discord->getLoop()->futureTick(function () use ($promise, $resolve) {
            $promise->cancel();
            $resolve('cancelled');
        });
    });

    expect($result)->toBe('cancelled');
});

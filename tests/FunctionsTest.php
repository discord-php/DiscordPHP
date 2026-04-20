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
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\contains;
use function Discord\escapeMarkdown;
use function Discord\getColor;
use function Discord\mentioned;
use function Discord\normalizePartId;
use function Discord\poly_strlen;
use function Discord\studly;

it('contains matches substrings', function (bool $expected, string $haystack, array $needles) {
    expect(contains($haystack, $needles))->toBe($expected);
})->with([
    [true, 'hello, world!', ['hello']],
    [true, 'phpunit tests', ['p', 'u']],
    [false, 'phpunit tests', ['a']],
]);

it('getColor resolves named and numeric colors', function (int $expected, string|int $color) {
    expect(getColor($color))->toBe($expected);
})->with([
    [0xcd5c5c, 'indianred'],
    [0x00bfff, 'deepskyblue'],
    [0x00bfff, 0x00bfff],
    [0, 0],
    [0x00bfff, '0x00bfff'],
]);

it('poly_strlen returns character count', function (int $expected, string $str) {
    expect(poly_strlen($str))->toBe($expected);
})->with([
    [5, 'abcde'],
    [0, ''],
    [1, ' '],
]);

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

it('studly converts strings to StudlyCase', function (string $input, string $expected) {
    expect(studly($input))->toBe($expected);
})->with([
    ['trains are cool', 'TrainsAreCool'],
    ['robo smells like bananas', 'RoboSmellsLikeBananas'],
    ['i LiKE TuRtLEs', 'ILikeTurtles'],
]);

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


<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\contains;
use function Discord\escapeMarkdown;
use function Discord\getColor;
use function Discord\mentioned;
use function Discord\normalizePartId;
use function Discord\poly_strlen;
use function Discord\studly;

final class FunctionsTest extends TestCase
{
    /**
     * @dataProvider containsProvider
     */
    public function testContains($expected, $needle, $haystack): void
    {
        $this->assertEquals($expected, contains($needle, $haystack));
    }

    public function containsProvider(): array
    {
        return [
            [true, 'hello, world!', ['hello']],
            [true, 'phpunit tests', ['p', 'u']],
            [false, 'phpunit tests', ['a']],
        ];
    }

    /**
     * @dataProvider colorProvider
     */
    public function testGetColor($expected, $color): void
    {
        $this->assertEquals($expected, getColor($color));
    }

    public function colorProvider(): array
    {
        return [
            [0xcd5c5c, 'indianred'],
            [0x00bfff, 'deepskyblue'],
            [0x00bfff, 0x00bfff],
            [0, 0],
            [0x00bfff, '0x00bfff'],
        ];
    }

    /**
     * @dataProvider strlenProvider
     */
    public function testPolyStrlen($expected, $string): void
    {
        $this->assertEquals($expected, poly_strlen($string));
    }

    public function strlenProvider(): array
    {
        return [
            [5, 'abcde'],
            [0, ''],
            [1, ' '],
        ];
    }

    /**
     * @test
     * @dataProvider mentionedProvider
     */
    public function testMentioned($part, $attributes, $outcome): void
    {
        $mockDiscord = getMockDiscord();

        $message = new Message(
            $mockDiscord,
            $attributes
        );

        $this->assertEquals($outcome, mentioned($part, $message));
    }

    public function mentionedProvider(): array
    {
        $mockDiscord = getMockDiscord();

        return [
            'member in mentions' => [
                new Member($mockDiscord, ['id' => '12345']),
                [
                    'mentions' => [(object) ['id' => '12345']],
                ],
                true,
            ],
            'member not in mentions' => [
                new Member($mockDiscord, ['id' => '123456']),
                [
                    'mentions' => [(object) ['id' => '12345']],
                ],
                false,
            ],

            'user in mentions' => [
                new User($mockDiscord, ['id' => '12345']),
                [
                    'mentions' => [(object) ['id' => '12345']],
                ],
                true,
            ],
            'user not in mentions' => [
                new User($mockDiscord, ['id' => '123456']),
                [
                    'mentions' => [(object) ['id' => '12345']],
                ],
                false,
            ],

            'user in mentions with several more' => [
                new User($mockDiscord, ['id' => '12345']),
                [
                    'mentions' => [(object) ['id' => '123456'], (object) ['id' => '1234567'], (object) ['id' => '12345']],
                ],
                true,
            ],
            'user not in mentions with several more' => [
                new User($mockDiscord, ['id' => '1234']),
                [
                    'mentions' => [(object) ['id' => '123456'], (object) ['id' => '1234567'], (object) ['id' => '12345']],
                ],
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider studlyCaseProvider
     */
    public function testStudlyCase(string $input, string $expected): void
    {
        $this->assertEquals($expected, studly($input));
    }

    public function studlyCaseProvider(): array
    {
        return [
            ['trains are cool', 'TrainsAreCool'],
            ['robo smells like bananas', 'RoboSmellsLikeBananas'],
            ['i LiKE TuRtLEs', 'ILikeTurtles'],
        ];
    }

    /**
     * @test
     * @dataProvider normalizePartIdProvider
     */
    public function testNormalizePartId($part, $expected): void
    {
        $this->assertEquals(
            $expected,
            (normalizePartId())(
                new OptionsResolver(),
                $part
            )
        );
    }

    public function normalizePartIdProvider(): array
    {
        $mockDiscord = getMockDiscord();

        return [
            [new User($mockDiscord, ['id' => '12345']), '12345'],
            [new Channel($mockDiscord, ['id' => '12345']), '12345'],
            [new Role($mockDiscord, ['id' => '12345']), '12345'],
            ['12345', '12345'],
            [null, null],
        ];
    }

    /**
     * @test
     * @dataProvider escapeMarkdownProvider
     */
    public function testEscapeMarkdown(string $input, string $expected): void
    {
        $this->assertEquals($expected, escapeMarkdown($input));
    }

    public function escapeMarkdownProvider(): array
    {
        return [
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
        ];
    }
}

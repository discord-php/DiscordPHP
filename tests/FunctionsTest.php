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
    public function testContains(): void
    {
        static $array = [
            [true, 'hello, world!', ['hello']],
            [true, 'phpunit tests', ['p', 'u']],
            [false, 'phpunit tests', ['a']],
        ];

        foreach ($array as $case) {
            $this->assertEquals($case[0], contains($case[1], $case[2]));
        }
    }

    public function testGetColor(): void
    {
        static $array = [
            [0xcd5c5c, 'indianred'],
            [0x00bfff, 'deepskyblue'],
            [0x00bfff, 0x00bfff],
            [0, 0],
            [0x00bfff, '0x00bfff'],
        ];

        foreach ($array as $case) {
            $this->assertEquals($case[0], getColor($case[1]));
        }
    }

    public function testPolyStrlen(): void
    {
        static $array = [
            [5, 'abcde'],
            [0, ''],
            [1, ' '],
        ];
        foreach ($array as $case) {
            $this->assertEquals($case[0], poly_strlen($case[1]));
        }
    }

    /**
     * @test
     */
    public function testMentioned(): void
    {
        $mockDiscord = getMockDiscord();

        static $array = [
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

        foreach ($array as $case) {
            $this->assertEquals($case[2], mentioned($case[0], new Message($mockDiscord, $case[1])));
        }
    }

    /**
     * @test
     */
    public function testStudlyCase(): void
    {
        static $array = [
            ['trains are cool', 'TrainsAreCool'],
            ['robo smells like bananas', 'RoboSmellsLikeBananas'],
            ['i LiKE TuRtLEs', 'ILikeTurtles'],
        ];
        foreach ($array as $case) {
            $this->assertEquals($case[1], studly($case[0]));
        }
    }

    /**
     * @test
     */
    public function testNormalizePartId(): void
    {
        $mockDiscord = getMockDiscord();

        static $array = [
            [new User($mockDiscord, ['id' => '12345']), '12345'],
            [new Channel($mockDiscord, ['id' => '12345']), '12345'],
            [new Role($mockDiscord, ['id' => '12345']), '12345'],
            ['12345', '12345'],
            [null, null],
        ];

        foreach ($array as $case) {
            $this->assertEquals(
                $case[1],
                (normalizePartId())(
                    new OptionsResolver(),
                    $case[0]
                )
            );
        }
    }

    /**
     * @test
     */
    public function testEscapeMarkdown(): void
    {
        static $array = [
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

        foreach ($array as $case) {
            $this->assertEquals($case[1], escapeMarkdown($case[0]));
        }
    }
}

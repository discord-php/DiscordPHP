<?php

use PHPUnit\Framework\TestCase;

use function Discord\contains;
use function Discord\getColor;
use function Discord\poly_strlen;

final class FunctionsTest extends TestCase
{
    public function containsProvider()
    {
        return [
            [true, 'hello, world!', ['hello']],
            [true, 'phpunit tests', ['p', 'u']],
            [false, 'phpunit tests', ['a']],
        ];
    }

    /**
     * @dataProvider containsProvider
     */
    public function testContains($expected, $needle, $haystack)
    {
        $this->assertEquals($expected, contains($needle, $haystack));
    }

    public function colorProvider()
    {
        return [
            [0xcd5c5c, 'indianred'],
            [0x00bfff, 'deepskyblue'],
            [0x00bfff, 0x00bfff],
            [0, 0],
            [0x00bfff, '0x00bfff']
        ];
    }

    /**
     * @dataProvider colorProvider
     */
    public function testGetColor($expected, $color)
    {
        $this->assertEquals($expected, getColor($color));
    }

    public function strlenProvider()
    {
        return [
            [5, 'abcde'],
            [0, ''],
            [1, ' '],
        ];
    }

    /**
     * @dataProvider strlenProvider
     */
    public function testPolyStrlen($expected, $string)
    {
        $this->assertEquals($expected, poly_strlen($string));
    }
}

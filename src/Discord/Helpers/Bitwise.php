<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Brick\Math\BigInteger;

/**
 * A compat helper to handle bitwise operation in 32 bit php wrapping BigInteger
 */
class Bitwise
{
    /**
     * @param BigInteger|int|float|string $a
     * @param BigInteger|int|float|string $b
     *
     * @return BigInteger|int $a & $b
     */
    public static function and($a, $b)
    {
        return (PHP_INT_SIZE == 4) ? BigInteger::of($a)->and($b) : $a & $b;
    }

    /**
     * @param BigInteger|int|float|string $a
     * @param BigInteger|int|float|string $b
     *
     * @return BigInteger|int $a | $b
     */
    public static function or($a, $b)
    {
        return (PHP_INT_SIZE == 4) ? BigInteger::of($a)->or($b) : $a | $b;

    }

    /**
     * @param BigInteger|int|float|string $a
     * @param BigInteger|int|float|string $b
     *
     * @return BigInteger|int $a ^ $b
     */
    public static function xor($a, $b)
    {
        return (PHP_INT_SIZE == 4) ? BigInteger::of($a)->xor($b) : $a ^ $b;
    }

    /**
     * @param BigInteger|int|float|string $a
     *
     * @return BigInteger|int ~ $a
     */
    public static function not($a)
    {
        return (PHP_INT_SIZE == 4) ? BigInteger::of($a)->not() : ~$a;
    }

    /**
     * @param BigInteger|int|float|string $a
     * @param BigInteger|int|float|string $b
     *
     * @return BigInteger|int $a << $b
     */
    public static function shiftLeft($a, $b)
    {
        return (PHP_INT_SIZE == 4) ? BigInteger::of($a)->shiftedLeft($b) : $a << $b;
    }

    /**
     * @param BigInteger|int|float|string $a
     * @param BigInteger|int|float|string $b
     *
     * @return BigInteger|int $a >> $b
     */
    public static function shiftRight($a, $b)
    {
        return (PHP_INT_SIZE == 4) ? BigInteger::of($a)->shiftedRight($b) : $a >> $b;
    }
}

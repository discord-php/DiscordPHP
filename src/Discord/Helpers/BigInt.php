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

/**
 * Polyfill to handle big integer operation in 32 bit PHP using ext-gmp.
 *
 * @since 10.0.0 Renamed from Bitwise to BigInt
 * @since 7.0.0
 */
final class BigInt
{
    private static bool $is_32_gmp = false;

    /**
     * Run a single check whether the GMP extension is loaded.
     * Internally used during Discord class construct.
     *
     * @internal
     *
     * @return bool true if GMP extension is loaded
     */
    public static function init(): bool
    {
        if (extension_loaded('gmp')) {
            self::$is_32_gmp = true;
        }

        return self::$is_32_gmp;
    }

    /**
     * @param \GMP|int|string $num1
     * @param \GMP|int|string $num2
     *
     * @return \GMP|int $num1 & $num2
     */
    public static function and($num1, $num2)
    {
        if (self::$is_32_gmp) {
            return \gmp_and(self::floatCast($num1), self::floatCast($num2));
        }

        return $num1 & $num2;
    }

    /**
     * @param \GMP|int|string $num1
     * @param \GMP|int|string $num2
     *
     * @return \GMP|int $num1 | $num2
     */
    public static function or($num1, $num2)
    {
        if (self::$is_32_gmp) {
            return \gmp_or(self::floatCast($num1), self::floatCast($num2));
        }

        return $num1 | $num2;
    }

    /**
     * @param \GMP|int|string $num1
     * @param \GMP|int|string $num2
     *
     * @return \GMP|int $num1 ^ $num2
     */
    public static function xor($num1, $num2)
    {
        if (self::$is_32_gmp) {
            return \gmp_xor(self::floatCast($num1), self::floatCast($num2));
        }

        return $num1 ^ $num2;
    }

    /**
     * @param \GMP|int|string $value
     *
     * @return \GMP|int ~ $value
     */
    public static function not($value)
    {
        if (self::$is_32_gmp) {
            return \gmp_sub(\gmp_neg(self::floatCast($value)), 1);
        }

        return ~$value;
    }

    /**
     * @param \GMP|int|string $num1
     * @param int             $num2
     *
     * @return \GMP|int $num1 << $num2
     */
    public static function shiftLeft($num1, int $num2)
    {
        if (self::$is_32_gmp) {
            return \gmp_mul(self::floatCast($num1), \gmp_pow(2, $num2));
        }

        return $num1 << $num2;
    }

    /**
     * @param \GMP|int|string $num1
     * @param int             $num2
     *
     * @return \GMP|int $num1 >> $num2
     */
    public static function shiftRight($num1, int $num2)
    {
        if (self::$is_32_gmp) {
            return \gmp_div(self::floatCast($num1), \gmp_pow(2, $num2));
        }

        return $num1 >> $num2;
    }

    /**
     * @param \GMP|int|string $num1
     * @param int             $num2
     *
     * @return bool $num1 & (1 << $num2)
     */
    public static function test($num1, int $num2): bool
    {
        if (self::$is_32_gmp) {
            return \gmp_testbit(self::floatCast($num1), $num2);
        }

        return $num1 & (1 << $num2);
    }

    /**
     * @param \GMP|int|string $num1
     * @param int             $num2
     *
     * @return \GMP|int $num1 |= (1 << $num2)
     */
    public static function set($num1, int $num2)
    {
        if (self::$is_32_gmp) {
            $gmp = \gmp_init(self::floatCast($num1));
            \gmp_setbit($gmp, $num2);

            return $gmp;
        }

        return $num1 |= (1 << $num2);
    }

    /**
     * @param \GMP|int|string $num1
     * @param \GMP|int|string $num2
     *
     * @return \GMP|int $num1 + $num2
     */
    public static function add($num1, $num2)
    {
        return (self::$is_32_gmp) ? \gmp_add(self::floatCast($num1), self::floatCast($num2)) : $num1 + $num2;
    }

    /**
     * @param \GMP|int|string $num1
     * @param \GMP|int|string $num2
     *
     * @return \GMP|int $num1 - $num2
     */
    public static function sub($num1, $num2)
    {
        return (self::$is_32_gmp) ? \gmp_sub(self::floatCast($num1), self::floatCast($num2)) : $num1 - $num2;
    }

    /**
     * Safely converts float to string, avoiding locale-dependent issues.
     *
     * @link https://github.com/brick/math/pull/20
     *
     * @param mixed $value if not a float, it is discarded
     *
     * @return string|mixed string if value is a float, otherwise discarded
     */
    public static function floatCast($value)
    {
        // Discard non float
        if (! is_float($value)) {
            return $value;
        }

        $currentLocale = setlocale(LC_NUMERIC, '0');
        setlocale(LC_NUMERIC, 'C');

        $result = (string) $value;

        setlocale(LC_NUMERIC, $currentLocale);

        return $result;
    }

    /**
     * @return bool Whether the GMP extension is loaded
     */
    public static function is32BitWithGMP(): bool
    {
        return self::$is_32_gmp;
    }
}

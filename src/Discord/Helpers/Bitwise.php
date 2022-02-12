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
 * Polyfill to handle bitwise operation in 32 bit php using ext-gmp.
 */
class Bitwise
{
    private static bool $is_32_gmp = false;

    /**
     * Run a single check whether the GMP extension is loaded, internally used
     * during Discord class construct.
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
     * @param \GMP|int|string $a
     * @param \GMP|int|string $b
     *
     * @return \GMP|int $a & $b
     */
    public static function and($a, $b)
    {
        if (self::$is_32_gmp) {
            return \gmp_and(self::floatCast($a), self::floatCast($b));
        }

        return $a & $b;
    }

    /**
     * @param \GMP|int|string $a
     * @param \GMP|int|string $b
     *
     * @return \GMP|int $a | $b
     */
    public static function or($a, $b)
    {
        if (self::$is_32_gmp) {
            return \gmp_or(self::floatCast($a), self::floatCast($b));
        }

        return $a | $b;
    }

    /**
     * @param \GMP|int|string $a
     * @param \GMP|int|string $b
     *
     * @return \GMP|int $a ^ $b
     */
    public static function xor($a, $b)
    {
        if (self::$is_32_gmp) {
            return \gmp_xor(self::floatCast($a), self::floatCast($b));
        }

        return $a ^ $b;
    }

    /**
     * @param \GMP|int|string $a
     *
     * @return \GMP|int ~ $a
     */
    public static function not($a)
    {
        if (self::$is_32_gmp) {
            return \gmp_sub(\gmp_neg(self::floatCast($a)), 1);
        }

        return ~$a;
    }

    /**
     * @param \GMP|int|string $a
     * @param int             $b
     *
     * @return \GMP|int $a << $b
     */
    public static function shiftLeft($a, int $b)
    {
        if (self::$is_32_gmp) {
            return \gmp_mul(self::floatCast($a), \gmp_pow(2, $b));
        }

        return $a << $b;
    }

    /**
     * @param \GMP|int|string $a
     * @param int             $b
     *
     * @return \GMP|int $a >> $b
     */
    public static function shiftRight($a, int $b)
    {
        if (self::$is_32_gmp) {
            return \gmp_div(self::floatCast($a), \gmp_pow(2, $b));
        }

        return $a >> $b;
    }

    /**
     * @param \GMP|int|string $a
     * @param int             $b
     *
     * @return bool $a & (1 << $b)
     */
    public static function test($a, int $b): bool
    {
        if (self::$is_32_gmp) {
            return \gmp_testbit(self::floatCast($a), $b);
        }

        return $a & (1 << $b);
    }

    /**
     * @param \GMP|int|string $a
     * @param int             $b
     *
     * @return \GMP|int $a |= (1 << $b)
     */
    public static function set($a, int $b)
    {
        if (self::$is_32_gmp) {
            $gmp = \gmp_init(self::floatCast($a));
            \gmp_setbit($gmp, $b);

            return $gmp;
        }

        return $a |= (1 << $b);
    }

    /**
     * Safely converts float to string, avoiding locale-dependent issues.
     *
     * @see https://github.com/brick/math/pull/20
     *
     * @param mixed $value if not a float, it is discarded
     *
     * @return mixed|string string if value is a float, otherwise discarded
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

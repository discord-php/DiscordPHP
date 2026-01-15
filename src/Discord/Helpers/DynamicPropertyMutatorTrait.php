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

namespace Discord\Helpers;

/**
 * @since 10.19.0
 */
trait DynamicPropertyMutatorTrait
{
    /**
     * Checks if there is a get mutator present.
     *
     * @param string $key The property name to check.
     *
     * @return string|false Either a string if it is a method or false.
     */
    protected function checkForGetMutator(string $key)
    {
        return method_exists($this, $str = 'get'.self::studly($key))
            ? $str
            : false;
    }

    /**
     * Checks if there is a set mutator present.
     *
     * @param string $key The property name to check.
     *
     * @return string|false Either a string if it is a method or false.
     */
    protected function checkForSetMutator(string $key)
    {
        return method_exists($this, $str = 'set'.self::studly($key))
            ? $str
            : false;
    }

    /**
     * Gets a property on the parent part.
     *
     * @param string $key The name of the property.
     *
     * @return mixed      Either the property if it exists or void.
     * @throws \Exception
     */
    protected function getProperty(string $key)
    {
        if ($str = $this->checkForGetMutator($key)) {
            return $this->{$str}();
        }
    }

    /**
     * Sets an property on the parent part.
     *
     * @param string $key   The name of the property.
     * @param mixed  $value The value of the property.
     */
    protected function setProperty(string $key, $value): void
    {
        if ($str = $this->checkForSetMutator($key)) {
            $this->{$str}($value);
        }
    }

    /**
     * Converts a string to studlyCase.
     *
     * This is a port of updated Laravel's implementation,
     * a non-regex with static cache. This method is private static
     * as we may move it outside this class in future.
     *
     * @param string $string The string to convert.
     *
     * @return string
     *
     * @since 10.0.0
     */
    private static function studly(string $string): string
    {
        static $studlyCache = [];

        if (isset($studlyCache[$string])) {
            return $studlyCache[$string];
        }

        $words = explode(' ', str_replace(['-', '_'], ' ', $string));

        $studlyWords = array_map('ucfirst', $words);

        return $studlyCache[$string] = implode($studlyWords);
    }

    /**
     * Handles dynamic get calls onto the part.
     *
     * @param string $key The attributes key.
     *
     * @return mixed The value of the attribute.
     *
     * @throws \Exception
     * @see self::getAttribute() This function forwards onto getAttribute.
     */
    public function __get(string $key)
    {
        return $this->getProperty($key);
    }

    /**
     * Handles dynamic set calls onto the part.
     *
     * @param string $key   The attributes key.
     * @param mixed  $value The attributes value.
     *
     * @see self::setAttribute() This function forwards onto setAttribute.
     */
    public function __set(string $key, $value): void
    {
        $this->setProperty($key, $value);
    }
}

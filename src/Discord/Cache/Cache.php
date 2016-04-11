<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Cache;

use Discord\Cache\Drivers\ApcCacheDriver;
use Discord\Cache\Drivers\ArrayCacheDriver;

/**
 * Provides an interface to cache items easily.
 */
class Cache
{
    /**
     * The cache driver instance.
     *
     * @var CacheInterface The cache driver in use.
     */
    protected static $cache;

    /**
     * The default cache TTL.
     *
     * @var int The default cache TTL.
     */
    protected static $defaultTtl;

    /**
     * Changes the cache driver.
     *
     * @param CacheInterface $driver The cache driver to set.
     *
     * @return void
     */
    public static function setCache(CacheInterface $driver)
    {
        self::$cache = $driver;
    }

    /**
     * Returns the cache name.
     *
     * @return string The cache name.
     */
    public static function getCacheName()
    {
        if (! self::$cache) {
            // No cache driver, we will check for APC or use array.
            if (function_exists('apc_cache_info')) {
                self::setCache(new ApcCacheDriver());
            } else {
                self::setCache(new ArrayCacheDriver());
            }
        }

        return self::$cache->name;
    }

    /**
     * Returns the default cache TTL.
     *
     * @return int The default cache TTL.
     */
    public static function getDefaultTtl()
    {
        if (empty(self::$defaultTtl)) {
            self::$defaultTtl = 300;
        }

        return self::$defaultTtl;
    }

    /**
     * Handles dynamic static calls onto the Cache
     * class and forwards them onto the Cache
     * driver.
     *
     * @param string $function The function name called.
     * @param array  $args     Arguments that were called.
     *
     * @return mixed The response from the Cache driver.
     */
    public static function __callStatic($function, $args)
    {
        if (! self::$cache) {
            // No cache driver, we will check for APC or use array.
            if (function_exists('apc_cache_info')) {
                self::setCache(new ApcCacheDriver());
            } else {
                self::setCache(new ArrayCacheDriver());
            }
        }

        if (! is_callable([self::$cache, $function])) {
            return;
        }

        return call_user_func_array([self::$cache, $function], $args);
    }
}

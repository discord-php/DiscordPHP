<?php

namespace Discord\Cache;

use Discord\Cache\CacheInterface;
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
		if (!self::$cache) {
			// No cache driver, we will check for APC or use array.
			if (function_exists('apc_cache_info')) {

			}

			self::setCache(new ArrayCacheDriver());
		}
		
		return self::$cache->name;
	}

	/**
	 * Handles dynamic static calls onto the Cache
	 * class and forwards them onto the Cache
	 * driver.
	 *
	 * @param string $function The function name called.
	 * @param array $args Arguments that were called.
	 *
	 * @return mixed The response from the Cache driver.
	 */
	public static function __callStatic($function, $args)
	{
		if (!self::$cache) {
			// No cache driver, we will check for APC or use array.
			if (function_exists('apc_cache_info')) {

			}

			self::setCache(new ArrayCacheDriver());
		}

		if (!is_callable([self::$cache, $function])) {
			return null;
		}

		return call_user_func_array([self::$cache, $function], $args);
	}
}
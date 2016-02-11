<?php

namespace Discord\Cache\Drivers;

use Discord\Cache\CacheInterface;

/**
 * The APC cache driver.
 */
class ApcCacheDriver implements CacheInterface
{
	/**
     * The Cache name.
     *
     * @var string The Cache name.
     */
    public $name = 'apc';

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		if ($this->has($key)) {
			return apc_fetch($key);
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($key, $value, $ttl = 300)
	{
		return apc_store($key, $value, $ttl);
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($key)
	{
		return apc_exists($key);
	}

	/**
	 * @{inheritdoc}
	 */
	public function unset($key)
	{
		if ($this->has($key)) {
			return apc_delete($key);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear()
	{
		apc_clear_cache();
	}
}
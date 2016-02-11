<?php

namespace Discord\Cache\Drivers;

use Discord\Cache\CacheInterface;

/**
 * The Array cache driver.
 */
class ArrayCacheDriver implements CacheInterface
{
	/**
	 * The Cache array.
	 *
	 * @var array The array that contains all Cache values.
	 */
	protected $cache = [];

	/**
     * The Cache name.
     *
     * @var string The Cache name.
     */
    public $name = 'array';

	/**
	 * {@inheritdoc}
	 */
	public function get($key)
	{
		if ($this->has($key)) {
			$this->checkForExpiry($key);
			return $this->cache[$key]['data'];
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($key, $value, $ttl = 300)
	{
		$this->cache[$key] = [
			'data' => $value,
			'ttl' => $ttl,
			'store_time' => microtime(true)
		];

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($key)
	{
		$this->checkForExpiry($key);
		return isset($this->cache[$key]);
	}

	/**
	 * @{inheritdoc}
	 */
	public function unset($key)
	{
		if ($this->has($key)) {
			$this->checkForExpiry($key);
			unset($this->cache[$key]);

			return true;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear()
	{
		$this->cache = [];
	}

	/**
	 * Checks if the object has expired.
	 *
	 * @param mixed $key The object key.
	 *
	 * @return void 
	 */
	protected function checkForExpiry($key)
	{
		if (!isset($this->cache[$key])) {
			return;
		}

		$ttl = $this->cache[$key]['ttl'];
		$store_time = $this->cache[$key]['store_time'];

		if (microtime(true) >= $store_time + $ttl) {
			unset($this->cache[$key]);

			return;
		}
	}
}
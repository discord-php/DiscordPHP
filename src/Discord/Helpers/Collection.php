<?php

namespace Discord\Helpers;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
	/**
	 * Get an item from the collection with a
	 * key and index.
	 *
	 * @param mixed $key 
	 * @param mixed $name 
	 * @param mixed $default
	 * @return mixed 
	 */
	public function get($key, $value, $default = null)
	{
		foreach ($this->items as $item) {
			if (isset($item[$key])) {
				if ($item[$key] == $value) {
					return $item;
				}
			}
		}	

		return $default;
	}
}
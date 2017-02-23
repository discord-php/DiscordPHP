<?php

namespace Discord\Helpers;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
	
    public function __construct($items = [])
    {
		parent::__construct($items);
    }


	public function get($key, $value = null)
	{
		if ($key === 'id' && $this->has($value))
		{
			return $this->offsetGet($value);
		}
		foreach ($this->items as $item)
		{
			if (is_array($item))
			{
				if ($item[$key] == $value)
				{
					return $item;
				}
			}
			elseif (is_object($item))
			{
				if ($item->{$key} == $value)
				{
					return $item;
				}
			}
		}
	}
	
	public function getAll($key, $value = null)
	{
        $collection = new self();

		foreach ($this->items as $item)
		{
			if ($item->{$key} === $value)
			{
				$collection->push($item);
			}
		}

		return $collection;
	}
	
    /**
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array An array of public attributes.
     */
    public function __debugInfo()
    {
		return $this->items;
    }
}

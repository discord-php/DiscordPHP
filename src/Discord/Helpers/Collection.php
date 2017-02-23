<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use ArrayIterator;
use IteratorAggregate;
use Judy;

class Collection implements IteratorAggregate
{
    protected $discrim; //useless now
	
	protected $items;

    public function __construct($items = [], $discrim = null, $type = 'string')
    {
		if ($type === 'int')
		{
			$this->items = new Judy(Judy::INT_TO_MIXED);
		}
		elseif ($type === 'string')
		{
			$this->items = new Judy(Judy::STRING_TO_MIXED);
		}
    }


	public function get($key, $value = null)
	{
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

	public function has($key)
	{
		return $this->offsetExists($key);
	}
	
	public function offsetExists($key)
	{
		return $this->items->offsetExists($key);
	}
	
	public function offsetGet($key)
	{
		return $this->items->offsetGet($key);
	}
	
	public function offsetSet($key, $value)
	{
		$this->items->offsetSet($key, $value); 
	}
	
	public function push($value)
	{
		$this->items->offsetSet($this->items->count(), $value);
	}
	
	public function pull($key)
	{
		$this->offsetUnset($key);
	}
	
	public function offsetUnset($key)
	{
		$this->items->offsetUnset($key);
	}
	
	public function count()
	{
		return $this->items->count();
	}
	
	public function all()
	{
		$items = [];
		foreach ($this->items as $item)
		{
			$items[] = $item;
		}
		return $items;
	}
	
	public function first()
	{
		return $this->items->offsetGet($this->items->first());
	}
	
	public function last()
	{
		return $this->items->offsetGet($this->items->last());
	}
	
	public function getIterator()
	{
		return new ArrayIterator($this->all());
	}

	public function memoryUsage()
	{
		return $this->items->memoryUsage();
	}
	
	public function size()
	{
		return $this->items->size();
	}

	public function __toString()
	{
		return json_encode($this->all());
	}
	
	public function __call($function, $params)
	{
		return call_user_func_array([$this->items, $function], $params);
	}
	
    /**
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array An array of public attributes.
     */
    public function __debugInfo()
    {
		return $this->all();
    }
}

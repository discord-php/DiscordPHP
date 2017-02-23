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

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{

	 /**
     * {@inheritdoc}
     *
     * @param string $discrim The discriminator.
     */
    public function __construct($items = [])
    {
		parent::__construct($items);
    }

    /**
     * Get an item from the collection with a key and value.
     *
     * @param mixed $key   The key to match with the value.
     * @param mixed $value The value to match with the key.
     *
     * @return mixed The value or null.
     */

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
	
	 /**
     * Gets a collection of items from the repository with a key and value.
     *
     * @param mixed $key   The key to match with the value.
     * @param mixed $value The value to match with the key.
     *
     * @return Collection A collection.
     */
	
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

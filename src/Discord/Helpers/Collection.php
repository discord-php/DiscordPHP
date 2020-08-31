<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Support\Arr;
use IteratorAggregate;
use JsonSerializable;
use Serializable;

/**
 * Collection of items. Inspired by Laravel Collections.
 */
class Collection implements ArrayAccess, Serializable, JsonSerializable, IteratorAggregate
{
    /**
     * The collection discriminator.
     *
     * @var string
     */
    private $discrim;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    private $items;

    /**
     * Class type allowed into the collection.
     *
     * @var string
     */
    private $class;

    /**
     * Create a new collection.
     *
     * @param mixed  $items
     * @param string $discrim
     * @param string $class
     */
    public function __construct($items = [], $discrim = 'id', $class = null)
    {
        $this->items = $items;
        $this->discrim = $discrim;
        $this->class = $class;
    }

    /**
     * Gets an item from the collection.
     *
     * @param string $discrim
     * @param string $key
     *
     * @return mixed
     */
    public function get($discrim, $key)
    {
        if ($discrim == $this->discrim && isset($this->items[$key])) {
            return $this->items[$key];
        }

        foreach ($this->items as $item) {
            if (is_array($item) && isset($item[$discrim]) && $item[$discrim] == $key) {
                return $item;
            } elseif (is_object($item) && property_exists($item, $discrim) && $item->{$discrim} == $key) {
                return $item;
            }
        }
    }

    /**
     * Pulls an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Fills an array of items into the collection.
     *
     * @param array $items
     *
     * @return this
     */
    public function fill($items)
    {
        foreach ($items as $item) {
            $this->pushItem($item);
        }

        return $this;
    }

    /**
     * Pushes items to the collection.
     *
     * @param mixed ...$items
     *
     * @return this
     */
    public function push(...$items)
    {
        foreach ($items as $item) {
            $this->pushItem($item);
        }

        return $this;
    }

    /**
     * Pushes a single item to the collection.
     *
     * @param mixed $item
     *
     * @return this
     */
    public function pushItem($item)
    {
        if (is_null($this->discrim)) {
            $this->items[] = $item;
            
            return $this;
        }
        
        if (! is_null($this->class) && ! ($item instanceof $this->class)) {
            return $this;
        }
        
        if (is_array($item)) {
            $this->items[$item[$this->discrim]] = $item;
        } elseif (is_object($item)) {
            $this->items[$item->{$this->discrim}] = $item;
        }

        return $this;
    }

    /**
     * Counts the amount of objects in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Checks if the array has an object.
     *
     * @param array ...$keys
     *
     * @return bool
     */
    public function has(...$keys)
    {
        foreach ($keys as $key) {
            if (! isset($this->items[$key])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Clears the collection.
     *
     * @return this
     */
    public function clear()
    {
        $this->items = [];
    }

    /**
     * Runs a callback over the collection and creates a new collection.
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $values = array_map($callback, array_values($this->items));

        return new Collection(array_combine($keys, $values), $this->discrim);
    }

    /**
     * If the collection has an offset.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * Gets an item from the collection.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * Sets an item into the collection.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }
    
    /**
     * Unsets an index from the collection.
     *
     * @param mixed offset
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Returns the string representation of the collection.
     *
     * @return string
     */
    public function serialize()
    {
        return json_encode($this->items);
    }

    /**
     * Unserializes the collection.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->items = json_decode($serialized);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->items;
    }

    /**
     * Returns an iterator for the collection.
     *
     * @return Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Returns an item that will be displayed for debugging.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->items;
    }
}

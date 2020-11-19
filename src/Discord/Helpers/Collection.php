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
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Serializable;
use Traversable;

/**
 * Collection of items. Inspired by Laravel Collections.
 */
class Collection implements ArrayAccess, Serializable, JsonSerializable, IteratorAggregate, Countable
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
     * @param mixed       $items
     * @param string      $discrim
     * @param string|null $class
     */
    public function __construct(array $items = [], ?string $discrim = 'id', ?string $class = null)
    {
        $this->items = $items;
        $this->discrim = $discrim;
        $this->class = $class;
    }

    /**
     * Gets an item from the collection.
     *
     * @param string $discrim
     * @param mixed  $key
     *
     * @return mixed
     */
    public function get(string $discrim, $key)
    {
        if ($discrim == $this->discrim && isset($this->items[$key])) {
            return $this->items[$key];
        }

        foreach ($this->items as $item) {
            if (is_array($item) && isset($item[$discrim]) && $item[$discrim] == $key) {
                return $item;
            } elseif (is_object($item) && $item->{$discrim} == $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Sets a value in the collection.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function set($offset, $value)
    {
        // Don't insert elements that are not of type class.
        if (! is_null($this->class) && ! ($value instanceof $this->class)) {
            return;
        }

        $this->offsetSet($offset, $value);
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
        if (isset($this->items[$key])) {
            $default = $this->items[$key];
            unset($this->items[$key]);
        }

        return $default;
    }

    /**
     * Fills an array of items into the collection.
     *
     * @param array $items
     *
     * @return Collection
     */
    public function fill(array $items): Collection
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
     * @return Collection
     */
    public function push(...$items): Collection
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
     * @return Collection
     */
    public function pushItem($item): Collection
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
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns the first element of the collection.
     *
     * @return mixed
     */
    public function first()
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return null;
    }

    /**
     * If the collection has an offset.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function isset($offset): bool
    {
        return $this->offsetExists($offset);
    }

    /**
     * Checks if the array has an object.
     *
     * @param array ...$keys
     *
     * @return bool
     */
    public function has(...$keys): bool
    {
        foreach ($keys as $key) {
            if (! isset($this->items[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Runs a filter callback over the collection and
     * returns a new collection based on the response
     * of the callback.
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function filter(callable $callback): Collection
    {
        $collection = new Collection([], $this->discrim, $this->class);

        foreach ($this->items as $item) {
            if ($callback($item)) {
                $collection->push($item);
            }
        }

        return $collection;
    }

    /**
     * Clears the collection.
     */
    public function clear(): void
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
    public function map(callable $callback): Collection
    {
        $keys = array_keys($this->items);
        $values = array_map($callback, array_values($this->items));

        return new Collection(array_combine($keys, $values), $this->discrim, $this->class);
    }

    /**
     * Converts the collection to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * If the collection has an offset.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
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
        return $this->items[$offset] ?? null;
    }

    /**
     * Sets an item into the collection.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    /**
     * Unsets an index from the collection.
     *
     * @param mixed offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Returns the string representation of the collection.
     *
     * @return string
     */
    public function serialize(): string
    {
        return json_encode($this->items);
    }

    /**
     * Unserializes the collection.
     *
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        $this->items = json_decode($serialized);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    /**
     * Returns an iterator for the collection.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Returns an item that will be displayed for debugging.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->items;
    }
}

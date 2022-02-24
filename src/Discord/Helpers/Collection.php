<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Collection of items. Inspired by Laravel Collections.
 */
class Collection implements ArrayAccess, JsonSerializable, IteratorAggregate, Countable
{
    /**
     * The collection discriminator.
     *
     * @var string
     */
    protected $discrim;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items;

    /**
     * Class type allowed into the collection.
     *
     * @var string
     */
    protected $class;

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
     * Creates a collection from an array.
     *
     * @param array  $items
     * @param string $discrim
     * @param string $class
     *
     * @return Collection
     */
    public static function from(array $items = [], ?string $discrim = 'id', ?string $class = null)
    {
        return new static($items, $discrim, $class);
    }

    /**
     * Creates a collection for a class.
     *
     * @param string $class
     * @param string $discrim
     *
     * @return Collection
     */
    public static function for(string $class, ?string $discrim = 'id')
    {
        return new static([], $discrim, $class);
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
     * Returns the last element of the collection.
     *
     * @return mixed
     */
    public function last()
    {
        $last = end($this->items);

        if ($last !== false) {
            reset($this->items);

            return $last;
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
     * Runs a filter callback over the collection and
     * returns the first item where the callback returns
     * `true` when given the item.
     *
     * Returns `null` if no items returns `true` when called in
     * the callback.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function find(callable $callback)
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
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
     * Merges another collection into this collection.
     *
     * @param Collection $collection
     *
     * @return Collection
     */
    public function merge(Collection $collection): Collection
    {
        $this->items = array_merge($this->items, $collection->toArray());

        return $this;
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
    #[\ReturnTypeWillChange]
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
     * Returns the string representation of the collection.
     *
     * @return string
     */
    public function __serialize(): array
    {
        return $this->items;
    }

    /**
     * Unserializes the collection.
     *
     * @param string $serialized
     */
    public function unserialize(string $serialized): void
    {
        $this->items = json_decode($serialized);
    }

    /**
     * Unserializes the collection.
     *
     * @param array $serialized
     */
    public function __unserialize(array $serialized): void
    {
        $this->items = $serialized;
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

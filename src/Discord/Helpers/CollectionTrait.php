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

trait CollectionTrait
{
    /**
     * Create a new Collection.
     *
     * @param array       $items
     * @param ?string     $discrim
     * @param ?string     $class
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
     * @param array       $items
     * @param ?string     $discrim
     * @param ?string     $class
     *
     * @return ExCollectionInterface
     */
    public static function from(array $items = [], ?string $discrim = 'id', ?string $class = null)
    {
        return new Collection($items, $discrim, $class);
    }

    /**
     * Creates a collection for a class.
     *
     * @param string  $class
     * @param ?string $discrim
     *
     * @return ExCollectionInterface
     */
    public static function for(string $class, ?string $discrim = 'id')
    {
        return new Collection([], $discrim, $class);
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
        if (null !== $this->class && ! ($value instanceof $this->class)) {
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
     * Shifts an item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        if (empty($this->items)) {
            return null;
        }

        reset($this->items);
        $key = key($this->items);
        $value = array_shift($this->items);

        return [$key => $value];
    }

    /**
     * Fills an array of items into the collection.
     *
     * @param ExCollectionInterface|array $items
     *
     * @return self
     */
    public function fill($items): self
    {
        $items = $items instanceof CollectionInterface
            ? $items->toArray()
            : $items;
        if (! is_array($items)) {
            throw new \InvalidArgumentException('The fill method only accepts arrays or CollectionInterface instances.');
        }

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
     * @return self
     */
    public function push(...$items): self
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
     * @return self
     */
    public function pushItem($item): self
    {
        if (null === $this->discrim) {
            $this->items[] = $item;

            return $this;
        }

        if (null !== $this->class && ! ($item instanceof $this->class)) {
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
     * Checks if the array has multiple offsets.
     *
     * @param string|int ...$keys
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
     * Searches for a given value within the collection and returns the corresponding key if successful.
     *
     * @param mixed $needle
     * @param bool  $strict [optional]
     *
     * @return string|int|false
     */
    public function search(mixed $needle, bool $strict = false): string|int|false
    {
        return array_search($needle, $this->items, $strict);
    }

    /**
     * Runs a filter callback over the collection and returns a new Collection
     * based on the response of the callback.
     *
     * @param callable $callback
     *
     * @return ExCollectionInterface
     *
     * @todo This method will be typed to return a CollectionInterface in v11
     */
    public function filter(callable $callback)
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
     * Runs a filter callback over the collection and returns the first item
     * where the callback returns `true` when given the item.
     *
     * @param callable $callback
     *
     * @return mixed `null` if no items returns `true` when called in the `$callback`.
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
     * Finds the key of the first item that matches the callback.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function find_key(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item)) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Checks if any item matches the callback.
     *
     * @param callable $callback
     *
     * @return bool
     */
    public function any(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if all items match the callback.
     *
     * @param callable $callback
     *
     * @return bool
     */
    public function all(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if (! $callback($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Splices the collection, removing a portion of the items and replacing them with the given replacement.
     *
     * @param int   $offset
     * @param ?int  $length
     * @param mixed $replacement
     *
     * @return self
     */
    public function splice(int $offset, ?int $length, mixed $replacement = []): self
    {
        array_splice($this->items, $offset, $length, $replacement);

        return $this;
    }

    /**
     * Clears the collection.
     */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * Slices the collection.
     *
     * @param int  $offset
     * @param ?int $length
     * @param bool $preserve_keys
     *
     * @return ExCollectionInterface
     */
    public function slice(int $offset, ?int $length = null, bool $preserve_keys = false)
    {
        $items = $this->items;

        $items = array_slice($items, $offset, $length, $preserve_keys);

        return new Collection($items, $this->discrim, $this->class);
    }

    /**
     * Sort through each item with a callback.
     *
     * @param callable|int|null $callback
     *
     * @return ExCollectionInterface
     */
    public function sort(callable|int|null $callback)
    {
        $items = $this->items;

        $callback && is_callable($callback)
            ? uasort($items, $callback)
            : asort($items, $callback ?? SORT_REGULAR);

        return new Collection($items, $this->discrim, $this->class);
    }

    /**
     * Gets the difference between the items.
     *
     * If a callback is provided and is callable, it uses `array_udiff_assoc` to compute the difference.
     * Otherwise, it uses `array_diff`.
     *
     * @param ExCollectionInterface|array $array
     * @param ?callable                 $callback
     *
     * @return ExCollectionInterface
     */
    public function diff($items, ?callable $callback = null)
    {
        $items = $items instanceof CollectionInterface
            ? $items->toArray()
            : $items;

        $diff = $callback && is_callable($callback)
            ? array_udiff_assoc($this->items, $items, $callback)
            : array_diff($this->items, $items);

        return new Collection($diff, $this->discrim, $this->class);
    }

    /**
     * Gets the intersection of the items.
     *
     * If a callback is provided and is callable, it uses `array_uintersect_assoc` to compute the intersection.
     * Otherwise, it uses `array_intersect`.
     *
     * @param ExCollectionInterface|array $array
     * @param ?callable                 $callback
     *
     * @return ExCollectionInterface
     */
    public function intersect($items, ?callable $callback = null)
    {
        $items = $items instanceof CollectionInterface
            ? $items->toArray()
            : $items;

        $diff = $callback && is_callable($callback)
            ? array_uintersect_assoc($this->items, $items, $callback)
            : array_intersect($this->items, $items);

        return new Collection($diff, $this->discrim, $this->class);
    }

    /**
     * Applies the given callback function to each item in the collection.
     *
     * @param callable $callback
     * @param mixed    $arg
     *
     * @return ExCollectionInterface
     */
    public function walk(callable $callback, mixed $arg)
    {
        $items = $this->items;

        array_walk($items, $callback, $arg);

        return new Collection($items, $this->discrim, $this->class);
    }

    /**
     * Reduces the collection to a single value using a callback function.
     *
     * @param callable $callback
     * @param ?mixed   $initial
     *
     * @return ExCollectionInterface
     */
    public function reduce(callable $callback, $initial = null)
    {
        $items = $this->items;

        $items = array_reduce($items, $callback, $initial);

        return new Collection($items, $this->discrim, $this->class);
    }

    /**
     * Runs a callback over the collection and creates a new Collection.
     *
     * @param callable $callback
     *
     * @return ExCollectionInterface
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $values = array_map($callback, array_values($this->items));

        return new Collection(array_combine($keys, $values), $this->discrim, $this->class);
    }

    /**
     * Returns unique items.
     *
     * @param int   $flags
     *
     * @return ExCollectionInterface
     */
    public function unique(int $flags = SORT_STRING)
    {
        return new Collection(array_unique($this->items, $flags), $this->discrim, $this->class);
    }

    /**
     * Merges another collection into this collection.
     *
     * @param $collection
     *
     * @return self
     */
    public function merge($collection): self
    {
        $items = $collection instanceof CollectionInterface
            ? $collection->toArray()
            : $collection;

        $this->items = array_merge($this->items, $items);

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
     * Converts the items into a new collection.
     *
     * @return ExCollectionInterface
     */
    public function collect()
    {
        return new Collection($this->items, $this->discrim, $this->class);
    }

    /**
     * @since 11.0.0
     *
     * Get the keys of the items.
     *
     * @return int[]|string[]
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * Get the values of the items.
     *
     * @return array
     */
    public function values(): array
    {
        return array_values($this->items);
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
        // Attempt to use the value's discrim property as the key if offset is null
        if (empty($offset)) {
            if (is_array($value) && isset($value[$this->discrim])) {
                $offset = $value[$this->discrim];
            } elseif (is_object($value) && property_exists($value, $this->discrim)) {
                $offset = $value->{$this->discrim};
            }
        }

        $this->items[$offset] = $value;
    }

    /**
     * Unsets an index from the collection.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Returns the string representation of the collection.
     *
     * @param int  $flags
     * @param ?int $depth
     *
     * @return string
     */
    public function serialize(int $flags = 0, ?int $depth = 512): string
    {
        return json_encode($this->items, $flags, $depth);
    }

    /**
     * Returns the string representation of the collection.
     *
     * @return array
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
     * @param ExCollectionInterface|array $data
     */
    public function __unserialize($data): void
    {
        if ($data instanceof CollectionInterface) {
            $data = $data->toArray();
        }
        if (! is_array($data)) {
            throw new \InvalidArgumentException('The __unserialize method only accepts arrays or CollectionInterface instances.');
        }

        $this->items = $data;
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
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
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

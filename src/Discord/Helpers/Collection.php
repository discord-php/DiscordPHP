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

use Discord\Cache\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Collections are the 'arrays' that we use. They are extended from
 * Laravel collections.
 *
 * @see https://laravel.com/docs/5.2/collections In depth documentation can be found on the Laravel website.
 */
class Collection extends BaseCollection
{
    /**
     * The cache key for the Collection.
     *
     * @var string The cache key.
     */
    protected $cacheKey;

    /**
     * {@inheritdoc}
     */
    public function __construct($items = [], $cacheKey = null)
    {
        $this->items    = $this->getArrayableItems($items);
        $this->cacheKey = $cacheKey;
    }

    /**
     * Get an item from the collection with a
     * key and index.
     *
     * @param mixed $key     The key that we will match with the name.
     * @param mixed $name    The name that we will match with the key.
     * @param mixed $default Returned if we can't find the part.
     *
     * @return mixed An object in the collection or $default.
     */
    public function get($key, $value = null, $default = null)
    {
        foreach ($this->items as $item) {
            if (is_callable([$item, 'getAttribute'])) {
                if (! empty($item->getAttribute($key))) {
                    if ($item[$key] == $value) {
                        return $item;
                    }
                }
            } else {
                if (is_array($item)) {
                    if (isset($item[$key])) {
                        if ($item[$key] == $value) {
                            return $item;
                        }
                    }
                } else {
                    if (isset($item->{$key})) {
                        if ($item->{$key} == $value) {
                            return $item;
                        }
                    }
                }
            }
        }

        return $default;
    }

    /**
     * Gets all items from the collection with a
     * key and index.
     *
     * @param mixed $key  The key that we will match with the name.
     * @param mixed $name The name that we will match with the key.
     *
     * @return Collection A collection of items.
     */
    public function getAll($key, $value)
    {
        $items = new self();

        foreach ($this->items as $item) {
            if (is_callable([$item, 'getAttribute'])) {
                if (! empty($item->getAttribute($key))) {
                    if ($item[$key] == $value) {
                        $items->push($item);
                    }
                }
            } else {
                if (is_array($item)) {
                    if (isset($item[$key])) {
                        if ($item[$key] == $value) {
                            $items->push($item);
                        }
                    }
                } else {
                    if (isset($item->{$key})) {
                        if ($item->{$key} == $value) {
                            $items->push($item);
                        }
                    }
                }
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function push($value, $setCache = true)
    {
        // if (isset($value->id)) {
            // $this->items[$value->id] = $value;
        // } else {
            $this->items[] = $value;
        // }

        if ($setCache && ! is_null($this->cacheKey)) {
            Cache::set($this->cacheKey, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function pull($key, $default = null, $setCache = true)
    {
        $value = Arr::pull($this->items, $key, $default);

        if ($setCache && ! is_null($this->cacheKey)) {
            Cache::set($this->cacheKey, $this);
        }

        return $value;
    }

    /**
     * Sets the cache key.
     *
     * @param string $key       The cache key to set.
     * @param bool   $updateNow Whether to set the collection to the cache now.
     *
     * @return self
     */
    public function setCacheKey($key, $updateNow = false)
    {
        $this->cacheKey = $key;

        if ($updateNow) {
            Cache::set($key, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }

        if (! is_null($this->cacheKey)) {
            Cache::set($this->cacheKey, $this);
        }
    }
}

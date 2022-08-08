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

use Discord\Discord;
use Discord\Parts\Part;
use React\Cache\CacheInterface;
use React\Promise\PromiseInterface;
use WeakReference;

/**
 * Wrapper for CacheInterface that tracks Repository items.
 *
 * @internal Used by AbstractRepository.
 *
 * @property-read string $key_prefix Cache key prefix.
 */
class CacheWrapper
{
    /**
     * @var Discord
     */
    protected $discord;

    /**
     * The actual ReactPHP CacheInterface.
     *
     * @var CacheInterface
     */
    public $interface;

    /**
     * Repository items array reference.
     *
     * @var WeakReference[] Cache Key => Cache Weak Reference.
     */
    protected $items;

    /**
     * The allowed class name to be unserialized
     *
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $key_prefix;

    /**
     * @param CacheInterface $cacheInterface The actual CacheInterface.
     * @param array          &$items         Repository items passed by reference.
     * @param string         $class          Object class name allowed for serialization.
     *
     * @internal
     */
    public function __construct(Discord $discord, CacheInterface $cacheInterface, &$items, string $class)
    {
        $this->discord = $discord;
        $this->interface = $cacheInterface;
        $this->items = &$items;
        $this->class = $class;

        $this->key_prefix = substr(strrchr($this->class, '\\'), 1) . '.';
    }

    /**
     * Get Part from cache
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return PromiseInterface<Part>
     */
    public function get($key, $default = null)
    {
        return $this->interface->get($this->key_prefix.$key, $default)->then(function ($value) use ($key) {
            if ($value === null) {
                unset($this->items[$key]);
            } else {
                $value = $this->discord->factory($this->class, json_decode($value), true);
                $this->items[$key] = WeakReference::create($value);
            }

            return $value;
        });
    }

    /**
     * Set Part into cache
     *
     * @param string $key
     * @param Part   $value
     *
     * @return PromiseInterface<bool>
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->interface->set($this->key_prefix.$key, $value->serialize(), $ttl)->then(function ($success) use ($key, $value) {
            if ($success) {
                $this->items[$key] = WeakReference::create($value);
            }

            return $success;
        });
    }

    /**
     * Delete Part from cache
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function delete($key)
    {
        return $this->interface->delete($this->key_prefix.$key)->then(function ($success) use ($key) {
            if ($success) {
                unset($this->items[$key]);
            }

            return $success;
        });
    }

    /**
     * Get multiple Parts from cache
     *
     * @param array $keys
     * @param ?Part $default
     *
     * @return PromiseInterface<array>
     */
    public function getMultiple(array $keys, $default = null)
    {
        $realKeys = array_map(function ($key) {
            return $this->key_prefix.$key;
        }, $keys);

        return $this->interface->getMultiple($realKeys, $default)->then(function ($values) use ($keys) {
            foreach ($keys as $key) {
                // Check if the prefixed key is returned
                if (! array_key_exists($this->key_prefix.$key, $values)) {
                    unset($this->items[$key]);
                    continue;
                }

                // Get real value from prefixed key
                $value = $values[$this->key_prefix.$key];

                if ($value === null) {
                    unset($this->items[$key]);
                } else {
                    $value = $this->discord->factory($this->class, json_decode($value), true);
                    $this->items[$key] = WeakReference::create($value);
                }

                // Remove real value with key prefix
                unset($values[$this->key_prefix.$key]);
            }

            return $values;
        });
    }

    /**
     * Set multiple Parts into cache
     *
     * @param array $values
     * @param ?int  $ttl
     *
     * @return PromiseInterface<bool>
     */
    public function setMultiple(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $valueRefs[$key] = WeakReference::create($value);

            // Replace values key with prefixed key
            $values[$this->key_prefix.$key] = $value->serialize();
            unset($values[$key]);
        }

        return $this->interface->setMultiple($values, $ttl)->then(function ($success) use ($valueRefs) {
            if ($success) {
                $this->items = array_merge($this->items, $valueRefs);
            }

            return $success;
        });
    }

    /**
     * Delete multiple Parts from cache
     *
     * @param array $keys
     *
     * @return PromiseInterface<bool>
     */
    public function deleteMultiple(array $keys)
    {
        $realKeys = array_map(function ($key) {
            return $this->key_prefix.$key;
        }, $keys);

        return $this->interface->deleteMultiple($realKeys)->then(function ($success) use ($keys) {
            if ($success) {
                foreach ($keys as $key) {
                    unset($this->items[$key]);
                }
            }

            return $success;
        });
    }

    /**
     * Clear all Parts from cache
     *
     * @return PromiseInterface<bool>
     */
    public function clear()
    {
        $realKeys = array_map(function ($key) {
            return $this->key_prefix.$key;
        }, $this->items);

        return $this->interface->deleteMultiple($realKeys)->then(function ($success) {
            if ($success) {
                $this->items = [];
            }

            return $success;
        });
    }

    /**
     * Check if Part is present in cache
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function has($key)
    {
        return $this->interface->has($this->key_prefix.$key)->then(function ($success) use ($key) {
            if (! $success) {
                unset($this->items[$key]);
            }

            return $success;
        });
    }

    public function __get(string $name)
    {
        if (in_array($name, ['key_prefix'])) {
            return $this->$name;
        }
    }
}

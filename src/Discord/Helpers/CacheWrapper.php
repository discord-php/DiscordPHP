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
     * @var null[]|Part[]|WeakReference[] Cache Key => Cache Part.
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
     * @var callable Callback flusher
     */
    protected $flusher;

    /**
     * @param CacheInterface $cacheInterface The actual CacheInterface.
     * @param array          &$items         Repository items passed by reference.
     * @param string         $class          Object class name allowed for serialization.
     * @param string[]       $vars           Variable containing hierarchy parent IDs.
     *
     * @internal
     */
    public function __construct(Discord $discord, CacheInterface $cacheInterface, &$items, string $class, array $vars)
    {
        $this->discord = $discord;
        $this->interface = $cacheInterface;
        $this->items = &$items;
        $this->class = &$class;

        $separator = '.';
        if (is_a($cacheInterface, '\WyriHaximus\React\Cache\Redis') || is_a($cacheInterface, 'seregazhuk\React\Cache\Memcached\Memcached') ) {
            $separator = ':';
        }

        $this->key_prefix = implode($separator, [substr(strrchr($this->class, '\\'), 1)] + $vars) . $separator;

        // Flush every heartbeat ack
        $this->flusher = function ($time, Discord $discord) {
            $values = [];
            foreach ($this->items as $key => $item) {
                if ($item === null || $item instanceof WeakReference) {
                    // Item was removed from memory, delete from cache
                    $values[] = $key;
                } elseif ($item instanceof Part) {
                    // Item is no longer used other than in the repository, make it weak so it can be deleted next heartbeat
                    $this->items[$key] = WeakReference::create($item);
                }
            }
            $flushed = count($values);
            if ($flushed) {
                $this->deleteMultiple($values)->then(function ($success) use ($flushed) {
                    if ($success) {
                        $this->discord->getLogger()->debug('Flushed repository cache', ['count' => $flushed, 'class' => $this->class]);
                    }
                });
            }
        };
        $discord->on('heartbeat-ack', $this->flusher);
    }

    public function __destruct()
    {
        $this->discord->removeListener('heartbeat-ack', $this->flusher);
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
                $value = $this->items[$key] = $this->discord->factory($this->class, json_decode($value), true);
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
                $this->items[$key] = $value;
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
                    $values[$key] = $this->items[$key] = $this->discord->factory($this->class, json_decode($value), true);
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
            $items[$this->key_prefix.$key] = $value->serialize();
        }

        return $this->interface->setMultiple($items, $ttl)->then(function ($success) use ($values) {
            if ($success) {
                $this->items = array_merge($this->items, $values);
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

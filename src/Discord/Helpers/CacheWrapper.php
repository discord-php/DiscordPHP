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
use React\Cache\ArrayCache;
use React\Cache\CacheInterface;
use React\Promise\PromiseInterface;
use WeakReference;

use function React\Promise\all;

/**
 * Wrapper for CacheInterface that store Repository items.
 *
 * Compatible with react/cache 0.5 - 1.x interface.
 *
 * @since 10.0.0
 *
 * @property-read CacheInterface $interface The actual ReactPHP CacheInterface.
 *
 * @internal
 */
class CacheWrapper
{
    /**
     * @var Discord
     */
    protected $discord;

    /**
     * @var CacheInterface
     */
    protected $interface;

    /**
     * Repository items array reference.
     *
     * @var ?Part[]|WeakReference[] Cache Key => Cache Part.
     */
    protected $items;

    /**
     * The item class name.
     *
     * @var string
     */
    protected $class;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * @var ?callable Callback flusher
     */
    protected $flusher;

    /**
     * @param Discord        $discord
     * @param CacheInterface $cacheInterface The actual CacheInterface.
     * @param array          &$items         Repository items passed by reference.
     * @param string         &$class         Part class name.
     * @param string[]       $vars           Variable containing hierarchy parent IDs.
     *
     * @internal
     */
    public function __construct(Discord $discord, CacheInterface $cacheInterface, &$items, string &$class, array $vars)
    {
        $this->discord = $discord;
        $this->interface = $cacheInterface;
        $this->items = &$items;
        $this->class = &$class;

        $separator = '.';
        if (is_a($cacheInterface, '\WyriHaximus\React\Cache\Redis') || is_a($cacheInterface, 'seregazhuk\React\Cache\Memcached\Memcached')) {
            $separator = ':';
        }

        $this->prefix = implode($separator, [substr(strrchr($this->class, '\\'), 1)] + $vars).$separator;

        if ($discord->options['cacheSweep']) {
            // Flush every heartbeat ack
            $this->flusher = function ($time, Discord $discord) {
                $flushing = 0;
                foreach ($this->items as $key => $item) {
                    if ($item === null) {
                        // Item was removed from memory, delete from cache
                        $this->delete($key);
                        $flushing++;
                    } elseif ($item instanceof Part) {
                        // Skip ID related to Bot
                        if ($key != $discord->id) {
                            // Item is no longer used other than in the repository, weaken so it can be garbage collected
                            $this->items[$key] = WeakReference::create($item);
                        }
                    }
                }
                if ($flushing) {
                    $this->discord->getLogger()->debug('Flushing repository cache', ['count' => $flushing, 'class' => $this->class]);
                }
            };
            $discord->on('heartbeat-ack', $this->flusher);
        }
    }

    public function __destruct()
    {
        if ($this->flusher) {
            $this->discord->removeListener('heartbeat-ack', $this->flusher);
        }
    }

    /**
     * Get Part from cache.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return PromiseInterface<Part>
     */
    public function get($key, $default = null)
    {
        return $this->interface->get($this->prefix.$key, $default)->then(function ($value) use ($key) {
            if ($value === null) {
                unset($this->items[$key]);
            } else {
                if (! ($this->interface instanceof ArrayCache)) {
                    $value = json_decode($value);
                }
                $value = $this->items[$key] = $this->discord->factory($this->class, $value, true);
            }

            return $value;
        });
    }

    /**
     * Set Part into cache.
     *
     * @param string $key
     * @param Part   $value
     *
     * @return PromiseInterface<bool>
     */
    public function set($key, $value, $ttl = null)
    {
        if ($this->interface instanceof ArrayCache) {
            $item = $value->getRawAttributes();
        } else {
            $item = $value->serialize();
        }

        return $this->interface->set($this->prefix.$key, $item, $ttl)->then(function ($success) use ($key, $value) {
            if ($success) {
                $this->items[$key] = $value;
            }

            return $success;
        });
    }

    /**
     * Delete Part from cache.
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function delete($key)
    {
        return $this->interface->delete($this->prefix.$key)->then(function ($success) use ($key) {
            if ($success) {
                unset($this->items[$key]);
            }

            return $success;
        });
    }

    /**
     * Get multiple Parts from cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param array $keys
     * @param ?Part $default
     *
     * @return PromiseInterface<array>
     */
    public function getMultiple(array $keys, $default = null)
    {
        $promises = [];

        foreach ($keys as $key) {
            $promises[$key] = $this->get($key, $default);
        }

        return all($promises);
    }

    /**
     * Set multiple Parts into cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param Part[] $values
     * @param ?int   $ttl
     *
     * @return PromiseInterface<bool>
     */
    public function setMultiple(array $values, $ttl = null)
    {
        $promises = [];

        foreach ($values as $key => $value) {
            $promises[$key] = $this->set($key, $value, $ttl);
        }

        return all($promises);
    }

    /**
     * Delete multiple Parts from cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param array $keys
     *
     * @return PromiseInterface<bool>
     */
    public function deleteMultiple(array $keys)
    {
        $promises = [];

        foreach ($keys as $key) {
            $promises[$key] = $this->delete($key);
        }

        return all($promises);
    }

    /**
     * Clear all Parts from cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @return PromiseInterface<bool>
     */
    public function clear()
    {
        $promises = [];

        foreach (array_keys($this->items) as $key) {
            $promises[$key] = $this->interface->delete($this->prefix.$key);
        }

        return all($promises)->then(function ($success) {
            if ($success) {
                $this->items = [];
            }

            return $success;
        });
    }

    /**
     * Check if Part is present in cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function has($key)
    {
        if (is_callable([$this->interface, 'has'])) {
            $promise = call_user_func([$this->interface, 'has'], $this->prefix.$key);
        } else {
            $promise = $this->get($this->prefix.$key);
        }

        return $promise->then(function ($value) use ($key) {
            if (! $value) {
                unset($this->items[$key]);
            }

            return (bool) $value;
        });
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    public function __get(string $name)
    {
        if (in_array($name, ['interface'])) {
            return $this->$name;
        }
    }
}
